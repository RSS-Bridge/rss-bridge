This page provides a collection of frequently asked questions and their answers. Please check this page before opening a new Issue :revolving_hearts:

* [Why doesn't my bridge show new contents?](#why-doesnt-my-bridge-show-new-contents)
* [How can I make a bridge update more frequently?](#how-can-i-make-a-bridge-update-more-frequently)
* [Firefox doesn't show feeds anymore, what can I do?](#firefox-doesnt-show-feeds-anymore-what-can-i-do)

## Why doesn't my bridge show new contents?

RSS-Bridge creates a cached version of your feed in order to reduce traffic and respond faster. The cached version is created on the first request and served for all subsequent requests. On every request RSS-Bridge checks if the cache timeout has elapsed. If the timeout has elapsed, it loads new contents and updates the cached version.

_Notice_: RSS-Bridge only updates feeds if you actively request it, for example by pressing F5 in your browser or using a feed reader.

The cache duration is bridge specific and can last anywhere between five minutes and 24 hours. You can specify a custom cache timeout for each bridge if [this option](#how-can-i-make-a-bridge-update-more-frequently) has been enabled on the server.

## How can I make a bridge update more frequently?

You can only do that if you are hosting the RSS-Bridge instance:
- Enable [`custom_timeout`](../03_For_Hosts/08_Custom_Configuration.md#customtimeout)
- Alternatively, change the default timeout for your bridge by modifying the `CACHE_TIMEOUT` constant in the relevant bridge file (e.g [here](https://github.com/RSS-Bridge/rss-bridge/blob/master/bridges/FilterBridge.php#L7) for the Filter Bridge).

## Firefox doesn't show feeds anymore, what can I do?

As of version 64, Firefox removed support for viewing Atom and RSS feeds in the browser. This results in the browser downloading the pages instead of showing contents.

Further reading:
- https://support.mozilla.org/en-US/kb/feed-reader-replacements-firefox
- https://bugzilla.mozilla.org/show_bug.cgi?id=1477667

To restore the original behavior in Firefox 64 or higher you can use following Add-on which attempts to recreate the original behavior (with some sugar on top):
- https://addons.mozilla.org/en-US/firefox/addon/rsspreview/