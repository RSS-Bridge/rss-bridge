import argparse
import requests
import re
from bs4 import BeautifulSoup
from datetime import datetime
from typing import Iterable
import os.path

# This script is specifically written to be used in automation for https://github.com/RSS-Bridge/rss-bridge
#
# This will scrape the whitelisted bridges in the current state (port 3000) and the PR state (port 3001) of
# RSS-Bridge, generate a feed for each of the bridges and save the output as html files.
# It also add a <base> tag with the url of em's public instance, so viewing
# the HTML file locally will actually work as designed.

class Instance:
    name = ''
    url = ''

def main(instances: Iterable[Instance], with_upload: bool, with_reduced_upload: bool, title: str, output_file: str):
    start_date = datetime.now()
    table_rows = []
    for instance in instances:
        page = requests.get(instance.url) # Use python requests to grab the rss-bridge main page
        soup = BeautifulSoup(page.content, "html.parser") # use bs4 to turn the page into soup
        bridge_cards = soup.select('.bridge-card') # get a soup-formatted list of all bridges on the rss-bridge page
        table_rows += testBridges(instance, bridge_cards, with_upload, with_reduced_upload) # run the main scraping code with the list of bridges
    with open(file=output_file, mode='w+', encoding='utf-8') as file:
        table_rows_value = '\n'.join(sorted(table_rows))
        file.write(f'''
## {title}
| Bridge | Context | Status |
| - | - | - |
{table_rows_value}

*last change: {start_date.strftime("%A %Y-%m-%d %H:%M:%S")}*
        '''.strip())

def testBridges(instance: Instance, bridge_cards: Iterable, with_upload: bool, with_reduced_upload: bool) -> Iterable:
    instance_suffix = ''
    if instance.name:
        instance_suffix = f' ({instance.name})'
    table_rows = []
    for bridge_card in bridge_cards:
        bridgeid = bridge_card.get('id')
        bridgeid = bridgeid.split('-')[1] # this extracts a readable bridge name from the bridge metadata
        print(f'{bridgeid}{instance_suffix}')
        bridgestring = '/?action=display&bridge=' + bridgeid + '&format=Html'
        bridge_name = bridgeid.replace('Bridge', '')
        context_forms = bridge_card.find_all("form")
        form_number = 1
        for context_form in context_forms:
            # a bridge can have multiple contexts, named 'forms' in html
            # this code will produce a fully working formstring that should create a working feed when called
            # this will create an example feed for every single context, to test them all
            formstring = ''
            error_messages = []
            context_name = '*untitled*'
            context_name_element = context_form.find_previous_sibling('h5')
            if context_name_element and context_name_element.text.strip() != '':
                context_name = context_name_element.text
            parameters = context_form.find_all("input")
            lists = context_form.find_all("select")
            # this for/if mess cycles through all available input parameters, checks if it required, then pulls
            # the default or examplevalue and then combines it all together into the formstring
            # if an example or default value is missing for a required attribute, it will throw an error
            # any non-required fields are not tested!!!
            for parameter in parameters:
                if parameter.get('type') == 'hidden' and parameter.get('name') == 'context':
                    cleanvalue = parameter.get('value').replace(" ","+")
                    formstring = formstring + '&' + parameter.get('name') + '=' + cleanvalue
                if parameter.get('type') == 'number' or parameter.get('type') == 'text':
                    if parameter.has_attr('required'):
                        if parameter.get('placeholder') == '':
                            if parameter.get('value') == '':
                                name_value = parameter.get('name')
                                error_messages.append(f'Missing example or default value for parameter "{name_value}"')
                            else:
                                formstring = formstring + '&' + parameter.get('name') + '=' + parameter.get('value')
                        else:
                            formstring = formstring + '&' + parameter.get('name') + '=' + parameter.get('placeholder')
                # same thing, just for checkboxes. If a checkbox is checked per default, it gets added to the formstring
                if parameter.get('type') == 'checkbox':
                    if parameter.has_attr('checked'):
                        formstring = formstring + '&' + parameter.get('name') + '=on'
            for listing in lists:
                selectionvalue = ''
                listname = listing.get('name')
                cleanlist = []
                for option in listing.contents:
                    if 'optgroup' in option.name:
                        cleanlist.extend(option)
                    else:
                        cleanlist.append(option)
                firstselectionentry = 1
                for selectionentry in cleanlist:
                    if firstselectionentry:
                        selectionvalue = selectionentry.get('value')
                        firstselectionentry = 0
                    else:
                        if 'selected' in selectionentry.attrs:
                            selectionvalue = selectionentry.get('value')
                            break
                formstring = formstring + '&' + listname + '=' + selectionvalue
            termpad_url = 'about:blank'
            if error_messages:
                status = '<br>'.join(map(lambda m: f'❌ `{m}`', error_messages))
            else:
                # if all example/default values are present, form the full request string, run the request, add a <base> tag with
                # the url of em's public instance to the response text (so that relative paths work, e.g. to the static css file) and
                # then upload it to termpad.com, a pastebin-like-site.
                response = requests.get(instance.url + bridgestring + formstring)
                page_text = response.text.replace('<head>','<head><base href="https://rss-bridge.org/bridge01/" target="_blank">')
                page_text = page_text.encode("utf_8")
                soup = BeautifulSoup(page_text, "html.parser")
                status_messages = []
                if response.status_code != 200:
                    status_messages += [f'❌ `HTTP status {response.status_code} {response.reason}`']
                else:
                    feed_items = soup.select('.feeditem')
                    feed_items_length = len(feed_items)
                    if feed_items_length <= 0:
                        status_messages += [f'⚠️ `The feed has no items`']
                    elif feed_items_length == 1 and len(soup.select('.error')) > 0:
                        status_messages += [f'❌ `{getFirstLine(feed_items[0].text)}`']
                status_messages += map(lambda e: f'❌ `{getFirstLine(e.text)}`', soup.select('.error .error-type') + soup.select('.error .error-message'))
                for item_element in soup.select('.feeditem'): # remove all feed items to not accidentally selected <pre> tags from item content
                    item_element.decompose()
                status_messages += map(lambda e: f'⚠️ `{getFirstLine(e.text)}`', soup.find_all('pre'))
                status_messages = list(dict.fromkeys(status_messages)) # remove duplicates
                status = '<br>'.join(status_messages)
                status_is_ok = status == '';
                if status_is_ok:
                    status = '✔️'
                if with_upload and (not with_reduced_upload or not status_is_ok):
                    termpad = requests.post(url="https://termpad.com/", data=page_text)
                    termpad_url = termpad.text.strip()
                    termpad_url = termpad_url.replace('termpad.com/','termpad.com/raw/')
            table_rows.append(f'| {bridge_name} | [{form_number} {context_name}{instance_suffix}]({termpad_url}) | {status} |')
            form_number += 1
    return table_rows

def getFirstLine(value: str) -> str:
     # trim whitespace and remove text that can break the table or is simply unnecessary
    clean_value = re.sub('^\[[^\]]+\]\s*rssbridge\.|[\|`]', '', value.strip())
    first_line = next(iter(clean_value.splitlines()), '')
    max_length = 250
    if (len(first_line) > max_length):
        first_line = first_line[:max_length] + '...'
    return first_line

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--instances', nargs='+')
    parser.add_argument('--no-upload', action='store_true')
    parser.add_argument('--reduced-upload', action='store_true')
    parser.add_argument('--title', default='Pull request artifacts')
    parser.add_argument('--output-file', default=os.getcwd() + '/comment.txt')
    args = parser.parse_args()
    instances = []
    if args.instances:
        for instance_arg in args.instances:
            instance_arg_parts = instance_arg.split('::')
            instance = Instance()
            instance.name = instance_arg_parts[1] if len(instance_arg_parts) >= 2 else ''
            instance.url = instance_arg_parts[0]
            instances.append(instance)
    else:
        instance = Instance()
        instance.name = 'current'
        instance.url = 'http://localhost:3000'
        instances.append(instance)
        instance = Instance()
        instance.name = 'pr'
        instance.url = 'http://localhost:3001'
        instances.append(instance)
    main(
        instances=instances,
        with_upload=not args.no_upload,
        with_reduced_upload=args.reduced_upload and not args.no_upload,
        title=args.title,
        output_file=args.output_file
    );