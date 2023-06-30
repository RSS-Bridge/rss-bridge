import requests
import itertools
from bs4 import BeautifulSoup
from datetime import datetime
import os.path

# This script is specifically written to be used in automation for https://github.com/RSS-Bridge/rss-bridge
#
# This will scrape the whitelisted bridges in the current state (port 3000) and the PR state (port 3001) of
# RSS-Bridge, generate a feed for each of the bridges and save the output as html files.
# It also replaces the default static CSS link with a hardcoded link to @em92's public instance, so viewing
# the HTML file locally will actually work as designed.

def testBridges(bridges,status):
    for bridge in bridges:
        if bridge.get('data-ref'): # Some div entries are empty, this ignores those
            bridgeid = bridge.get('id')
            bridgeid = bridgeid.split('-')[1] # this extracts a readable bridge name from the bridge metadata
            print(bridgeid + "\n")
            bridgestring = '/?action=display&bridge=' + bridgeid + '&format=Html'
            forms = bridge.find_all("form")
            formid = 1
            for form in forms:
                # a bridge can have multiple contexts, named 'forms' in html
                # this code will produce a fully working formstring that should create a working feed when called
                # this will create an example feed for every single context, to test them all
                formstring = ''
                errormessages = []
                parameters = form.find_all("input")
                lists = form.find_all("select")
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
                                    errormessages.append(parameter.get('name'))
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
                if not errormessages:
                    # if all example/default values are present, form the full request string, run the request, replace the static css
                    # file with the url of em's public instance and then upload it to termpad.com, a pastebin-like-site.
                    r = requests.get(URL + bridgestring + formstring)
                    pagetext = r.text.replace('static/style.css','https://rss-bridge.org/bridge01/static/style.css')
                    pagetext = pagetext.encode("utf_8")
                    termpad = requests.post(url="https://termpad.com/", data=pagetext)
                    termpadurl = termpad.text
                    termpadurl = termpadurl.replace('termpad.com/','termpad.com/raw/')
                    termpadurl = termpadurl.replace('\n','')
                    with open(os.getcwd() + '/comment.txt', 'a+') as file:
                        file.write("\n")
                        file.write("| [`" + bridgeid + '-' + status + '-context' + str(formid) + "`](" + termpadurl + ") | " + date_time + " |")
                else:
                    # if there are errors (which means that a required value has no example or default value), log out which error appeared
                    termpad = requests.post(url="https://termpad.com/", data=str(errormessages))
                    termpadurl = termpad.text
                    termpadurl = termpadurl.replace('termpad.com/','termpad.com/raw/')
                    termpadurl = termpadurl.replace('\n','')
                    with open(os.getcwd() + '/comment.txt', 'a+') as file:
                        file.write("\n")
                        file.write("| [`" + bridgeid + '-' + status + '-context' + str(formid) + "`](" + termpadurl + ") | " + date_time + " |")
                formid += 1

gitstatus = ["current", "pr"]
now = datetime.now()
date_time = now.strftime("%Y-%m-%d, %H:%M:%S")

with open(os.getcwd() + '/comment.txt', 'w+') as file:
    file.write(''' ## Pull request artifacts
| file | last change |
| ---- | ------ |''')

for status in gitstatus: # run this twice, once for the current version, once for the PR version
    if status == "current":
        port = "3000" # both ports are defined in the corresponding workflow .yml file
    elif status == "pr":
        port = "3001"
    URL = "http://localhost:" + port
    page = requests.get(URL) # Use python requests to grab the rss-bridge main page
    soup = BeautifulSoup(page.content, "html.parser") # use bs4 to turn the page into soup
    bridges = soup.find_all("section") # get a soup-formatted list of all bridges on the rss-bridge page
    testBridges(bridges,status) # run the main scraping code with the list of bridges and the info if this is for the current version or the pr version
