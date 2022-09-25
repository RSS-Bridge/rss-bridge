InstagramBridge
===============

To somehow bypass the [rate limiting issue](https://github.com/RSS-Bridge/rss-bridge/issues/1891)
it is suggested to deploy a private RSS-Bridge instance that uses a working Instagram account.

Configuration
-------------

1. Retreiving `session id` and `ds_user_id`.
The following steps describe how to get the `session id` and `ds user id` using a Chromium-based browser.

- Create an Instagram account, that you will use for your RSS-Bridge instance.
It is NOT recommended to use your existing account that is used for common interaction with Instagram services.

- Login to Instagram

- Open DevTools by pressing F12

- Open "Networks tab"

- In the "Filter" field input "i.instagram.com"

- Click on "Fetch/XHR"

- Refresh web page

- Click on any item from the table of http requests

- In the new frame open the "Headers" tab and scroll to "Request Headers"

- There will be a cookie param will lots of `<key>=<value>;` text. You need the value of the "sessionid" and "ds_user_id" keys. Copy them.

2. Configuring RSS-Bridge

- In config.ini.php add following configuration:

```
[InstagramBridge]
session_id = %sessionid from step 1%
ds_user_id = %ds_user_id from step 1%
cache_timeout = %cache timeout in seconds%
```

The bigger the cache_timeout value, the smaller the chance for RSS-Bridge to throw 429 errors.
Default cache_timeout is 3600 seconds (1 hour).
