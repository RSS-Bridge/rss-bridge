=================
 InstagramBridge
=================

To somehow bypass `rate limiting issue <https://github.com/RSS-Bridge/rss-bridge/issues/1891>`_,
it is suggested to deploy private RSS-Bridge that uses working Instagram account.

Configuration
-------------

1. Retreiving session id.
Following steps describe how to get it using Chromium based browser.

- Create Instagram account, that you will use for your RSS-Bridge instance.
It is NOT recommended to use existing account, that is used for common interaction with Instagram services.

- Login to Instagram

- Open DevTools by pressing F12

- Open "Networks tab"

- In "Filter" field input "i.instagram.com"

- Click on "Fetch/XHR"

- Click on any item from table with http requests

- In new fram open "Headers" tab and scroll to "Request headers"

- There will be cookie param will lots of "key=value" text. You need the value of "sessionid" key. Copy it

2. Configurating RSS-Bridge

- In config.ini.php add following configuration:

.. code-block::

   [InstagramBridge]
   session_id = %sessionid from step 1%
   cache_timeout = %cache timeout in seconds%

The more cache_timeout value, less chances for RSS-Bridge to throw 429 errors.
Default cache_timeout is 3600 seconds (1 hour).
