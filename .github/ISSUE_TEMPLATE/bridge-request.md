---
name: Bridge request
about: Use this template for requesting a new bridge
title: Bridge request for ...
labels: Bridge-Request
assignees: ''

---

# Bridge request

<!--
This is a bridge request. Start by adding a descriptive title (i.e. `Bridge request for GitHub`). Use the "Preview" button to see a preview of your request. Make sure your request is complete before submitting!

Notice: This comment is only visible to you while you work on your request. Please do not remove any of the lines in the template (you may add your own outside the "<!--" and "- ->" lines!)
-->

## General information

<!--
Please describe what you expect from the bridge. Whenever possible provide sample links and screenshots (you can just paste them here) to express your expectations and help others understand your request. If possible, mark relevant areas in your screenshot. Use the following questions for reference:
-->

- _Host URI for the bridge_ (i.e. `https://github.com`):

- Which information would you like to see?



- How should the information be displayed/formatted?



- Which of the following parameters do you expect?

  - [X] Title
  - [X] URI (link to the original article)
  - [ ] Author
  - [ ] Timestamp
  - [X] Content (the content of the article)
  - [ ] Enclosures (pictures, videos, etc...)
  - [ ] Categories (categories, tags, etc...)

## Options

<!--Select options from the list below. Add your own option if one is missing:-->

- [ ] Limit number of returned items
  - _Default limit_: 5
- [ ] Load full articles
  - _Cache articles_ (articles are stored in a local cache on first request): yes
  - _Cache timeout_ : 24 hours
- [X] Balance requests (RSS-Bridge uses cached versions to reduce bandwith usage)
  - _Timeout_ (default = 5 minutes): 5 minutes

<!--Be aware that some options might not be available for your specific request due to technical limitations!-->

<!--
## Additional notes

Keep in mind that opening a request does not guarantee the bridge being implemented! That depends entirely on the interest and time of others to make the bridge for you.

You can also implement your own bridge (with support of the community if needed). Find more information in the [RSS-Bridge Documentation](https://rss-bridge.github.io/rss-bridge/For_Developers/index.html) developer section.
-->
