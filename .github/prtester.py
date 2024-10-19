import argparse
import requests
import re
from bs4 import BeautifulSoup
from datetime import datetime
from typing import Iterable
import os
import glob
import urllib

# This script is specifically written to be used in automation for https://github.com/RSS-Bridge/rss-bridge
#
# This will scrape the whitelisted bridges in the current state (port 3000) and the PR state (port 3001) of
# RSS-Bridge, generate a feed for each of the bridges and save the output as html files.
# It also add a <base> tag with the url of em's public instance, so viewing
# the HTML file locally will actually work as designed.

ARTIFACT_FILE_EXTENSION = '.html'

class Instance:
    name = ''
    url = ''

def main(instances: Iterable[Instance], with_upload: bool, with_reduced_upload: bool, title: str, output_file: str):
    start_date = datetime.now()

    prid = os.getenv('PR')
    artifact_base_url = f'https://rss-bridge.github.io/rss-bridge-tests/prs/{prid}'
    artifact_directory = os.getcwd()
    for file in glob.glob(f'*{ARTIFACT_FILE_EXTENSION}', root_dir=artifact_directory):
        os.remove(file)

    table_rows = []
    for instance in instances:
        page = requests.get(instance.url) # Use python requests to grab the rss-bridge main page
        soup = BeautifulSoup(page.content, "html.parser") # use bs4 to turn the page into soup
        bridge_cards = soup.select('.bridge-card') # get a soup-formatted list of all bridges on the rss-bridge page
        table_rows += testBridges(
            instance=instance,
            bridge_cards=bridge_cards,
            with_upload=with_upload,
            with_reduced_upload=with_reduced_upload,
            artifact_directory=artifact_directory,
            artifact_base_url=artifact_base_url) # run the main scraping code with the list of bridges
    with open(file=output_file, mode='w+', encoding='utf-8') as file:
        table_rows_value = '\n'.join(sorted(table_rows))
        file.write(f'''
## {title}
| Bridge | Context | Status |
| - | - | - |
{table_rows_value}

*last change: {start_date.strftime("%A %Y-%m-%d %H:%M:%S")}*
        '''.strip())

def testBridges(instance: Instance, bridge_cards: Iterable, with_upload: bool, with_reduced_upload: bool, artifact_directory: str, artifact_base_url: str) -> Iterable:
    instance_suffix = ''
    if instance.name:
        instance_suffix = f' ({instance.name})'
    table_rows = []
    for bridge_card in bridge_cards:
        bridgeid = bridge_card.get('id')
        bridgeid = bridgeid.split('-')[1] # this extracts a readable bridge name from the bridge metadata
        print(f'{bridgeid}{instance_suffix}')
        bridge_name = bridgeid.replace('Bridge', '')
        context_forms = bridge_card.find_all("form")
        form_number = 1
        for context_form in context_forms:
            # a bridge can have multiple contexts, named 'forms' in html
            # this code will produce a fully working url that should create a working feed when called
            # this will create an example feed for every single context, to test them all
            context_parameters = {}
            error_messages = []
            context_name = '*untitled*'
            context_name_element = context_form.find_previous_sibling('h5')
            if context_name_element and context_name_element.text.strip() != '':
                context_name = context_name_element.text
            parameters = context_form.find_all("input")
            lists = context_form.find_all("select")
            # this for/if mess cycles through all available input parameters, checks if it required, then pulls
            # the default or examplevalue and then combines it all together into the url parameters
            # if an example or default value is missing for a required attribute, it will throw an error
            # any non-required fields are not tested!!!
            for parameter in parameters:
                parameter_type = parameter.get('type')
                parameter_name = parameter.get('name')
                if parameter_type == 'hidden':
                    context_parameters[parameter_name] = parameter.get('value')
                if parameter_type == 'number' or parameter_type == 'text':
                    if parameter.has_attr('required'):
                        if parameter.get('placeholder') == '':
                            if parameter.get('value') == '':
                                error_messages.append(f'Missing example or default value for parameter "{parameter_name}"')
                            else:
                                context_parameters[parameter_name] = parameter.get('value')
                        else:
                            context_parameters[parameter_name] = parameter.get('placeholder')
                # same thing, just for checkboxes. If a checkbox is checked per default, it gets added to the url parameters
                if parameter_type == 'checkbox':
                    if parameter.has_attr('checked'):
                        context_parameters[parameter_name] = 'on'
            for listing in lists:
                selectionvalue = ''
                listname = listing.get('name')
                cleanlist = []
                options = listing.find_all('option')
                for option in options:
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
                context_parameters[listname] = selectionvalue
            artifact_url = 'about:blank'
            if error_messages:
                status = '<br>'.join(map(lambda m: f'❌ `{m}`', error_messages))
            else:
                # if all example/default values are present, form the full request url, run the request, add a <base> tag with
                # the url of em's public instance to the response text (so that relative paths work, e.g. to the static css file) and
                # then save it to a html file.
                context_parameters.update({
                    'action': 'display',
                    'bridge': bridgeid,
                    'format': 'Html',
                })
                request_url = f'{instance.url}/?{urllib.parse.urlencode(context_parameters)}'
                response = requests.get(request_url)
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
                    filename = f'{bridge_name} {form_number}{instance_suffix}{ARTIFACT_FILE_EXTENSION}'
                    filename = re.sub(r'[^a-z0-9 \_\-\.]', '', filename, flags=re.I).replace(' ', '_')
                    with open(file=f'{artifact_directory}/{filename}', mode='wb') as file:
                        file.write(page_text)
                    artifact_url = f'{artifact_base_url}/{filename}'
            table_rows.append(f'| {bridge_name} | [{form_number} {context_name}{instance_suffix}]({artifact_url}) | {status} |')
            form_number += 1
    return table_rows

def getFirstLine(value: str) -> str:
     # trim whitespace and remove text that can break the table or is simply unnecessary
    clean_value = re.sub(r'^\[[^\]]+\]\s*rssbridge\.|[\|`]', '', value.strip())
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
            instance.name = instance_arg_parts[1].strip() if len(instance_arg_parts) >= 2 else ''
            instance.url = instance_arg_parts[0].strip().rstrip("/")
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
