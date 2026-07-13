RedditBridge
============

Since Reddit shut down unauthorized JSON API access:
https://www.reddit.com/r/modnews/comments/1tq9vxo/protecting_communities_from_scrapers_and_platform/

Unfortunately, Reddit also blocked "easy" setup for developer apps:
https://old.reddit.com/wiki/api#wiki_read_the_full_api_terms_and_sign_up_for_usage

So currently, to get the bridge to work you need to either:

 - Submit a request for an app using Reddit's Data API **and somehow get it approved by Reddit**.

 - Already have an existing app - they still seem to work.

In both cases you need to have a private "script" app and its "id" and "secret":
https://old.reddit.com/prefs/apps

"Id" should be visible right in the base view; secret is available when selecting "edit".

You'll also need a private RSS-Bridge instance to set up your own configuration.

In `config.ini.php` add the following configuration:

```ini
[RedditBridge]
app_id = "<ID>"
app_secret = "<secret>"
```

The bridge will handle OAuth, refresh bearer, etc.
