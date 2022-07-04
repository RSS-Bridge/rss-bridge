![RSS-Bridge](static/logo_600px.png)
===
[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE)
[![GitHub release](https://img.shields.io/github/release/rss-bridge/rss-bridge.svg?logo=github)](https://github.com/rss-bridge/rss-bridge/releases/latest)
[![irc.libera.chat](https://img.shields.io/badge/irc.libera.chat-%23rssbridge-blue.svg)](https://web.libera.chat/#rssbridge)
[![Chat on Matrix](https://matrix.to/img/matrix-badge.svg)](https://matrix.to/#/#rssbridge:libera.chat)
[![Actions Status](https://img.shields.io/github/workflow/status/RSS-Bridge/rss-bridge/Tests/master?label=GitHub%20Actions&logo=github)](https://github.com/RSS-Bridge/rss-bridge/actions)
[![Docker Build Status](https://img.shields.io/docker/cloud/build/rssbridge/rss-bridge?logo=docker)](https://hub.docker.com/r/rssbridge/rss-bridge/)

RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one. It can be used on webservers or as a stand-alone application in CLI mode.

**Important**: RSS-Bridge is __not__ a feed reader or feed aggregator, but a tool to generate feeds that are consumed by feed readers and feed aggregators. Find a list of feed aggregators on [Wikipedia](https://en.wikipedia.org/wiki/Comparison_of_feed_aggregators).

Supported sites/pages (examples)
===

* `Bandcamp` : Returns last release from [bandcamp](https://bandcamp.com/) for a tag
* `Cryptome` : Returns the most recent documents from [Cryptome.org](https://cryptome.org/)
* `DansTonChat`: Most recent quotes from [danstonchat.com](https://danstonchat.com/)
* `DuckDuckGo`: Most recent results from [DuckDuckGo.com](https://duckduckgo.com/)
* `Facebook` : Returns the latest posts on a page or profile on [Facebook](https://facebook.com/) (There is an [issue](https://github.com/RSS-Bridge/rss-bridge/issues/2047) for public instances)
* `FlickrExplore` : [Latest interesting images](https://www.flickr.com/explore) from Flickr
* `GoogleSearch` : Most recent results from Google Search
* `Identi.ca` : Identica user timeline (Should be compatible with other Pump.io instances)
* `Instagram`: Most recent photos from an Instagram user (It is recommended to [configure](https://rss-bridge.github.io/rss-bridge/Bridge_Specific/Instagram.html) this bridge to work)
* `OpenClassrooms`: Lastest tutorials from [openclassrooms.com](https://openclassrooms.com/)
* `Pinterest`: Most recent photos from user or search
* `ScmbBridge`: Newest stories from [secouchermoinsbete.fr](https://secouchermoinsbete.fr/)
* `ThePirateBay` : Returns the newest indexed torrents from [The Pirate Bay](https://thepiratebay.se/) with keywords
* `Twitter` : Return keyword/hashtag search or user timeline
* `Wikipedia`: highlighted articles from [Wikipedia](https://wikipedia.org/) in English, German, French or Esperanto
* `YouTube` : YouTube user channel, playlist or search

And [many more](bridges/), thanks to the community!

Output format
===

RSS-Bridge is capable of producing several output formats:

* `Atom` : Atom feed, for use in feed readers
* `Html` : Simple HTML page
* `Json` : JSON, for consumption by other applications
* `Mrss` : MRSS feed, for use in feed readers
* `Plaintext` : Raw text, for consumption by other applications

You can extend RSS-Bridge with your own format, using the [Format API](https://rss-bridge.github.io/rss-bridge/Format_API/index.html)!

Screenshot
===

Welcome screen:

![Screenshot](/static/screenshot_rss-bridge_welcome.png?raw=true)

RSS-Bridge hashtag (#rss-bridge) search on Twitter, in Atom format (as displayed by Firefox):

![Screenshot](/static/screenshot_twitterbridge_atom.png?raw=true)

Requirements
===

RSS-Bridge requires PHP 7.4 or higher with following extensions enabled:

  - [`openssl`](https://secure.php.net/manual/en/book.openssl.php)
  - [`libxml`](https://secure.php.net/manual/en/book.libxml.php)
  - [`mbstring`](https://secure.php.net/manual/en/book.mbstring.php)
  - [`simplexml`](https://secure.php.net/manual/en/book.simplexml.php)
  - [`curl`](https://secure.php.net/manual/en/book.curl.php)
  - [`json`](https://secure.php.net/manual/en/book.json.php)
  - [`filter`](https://secure.php.net/manual/en/book.filter.php)
  - [`zip`](https://secure.php.net/manual/en/book.zip.php) (for some bridges)
  - [`sqlite3`](https://www.php.net/manual/en/book.sqlite3.php) (only when using SQLiteCache)

Find more information on our [Documentation](https://rss-bridge.github.io/rss-bridge/index.html)

Enable / Disable bridges
===

RSS-Bridge allows you to take full control over which bridges are displayed to the user. That way you can host your own RSS-Bridge service with your favorite collection of bridges!

Find more information on the [Documentation](https://rss-bridge.github.io/rss-bridge/For_Hosts/Whitelisting.html)

**Notice**: By default, RSS-Bridge will only show a small subset of bridges. Make sure to read up on [whitelisting](https://rss-bridge.github.io/rss-bridge/For_Hosts/Whitelisting.html) to unlock the full potential of RSS-Bridge!

Deploy
===

Thanks to the community, hosting your own instance of RSS-Bridge is as easy as clicking a button!
*Note: External providers' applications are packaged by 3rd parties. Use at your own discretion.*

[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
[![Deploy to Cloudron](https://cloudron.io/img/button.svg)](https://www.cloudron.io/store/com.rssbridgeapp.cloudronapp.html)

Getting involved
===

There are many ways for you to getting involved with RSS-Bridge. Here are a few things:

- Share RSS-Bridge with your friends (Twitter, Facebook, ..._you name it_...)
- Report broken bridges or bugs by opening [Issues](https://github.com/RSS-Bridge/rss-bridge/issues) on GitHub
- Request new features or suggest ideas (via [Issues](https://github.com/RSS-Bridge/rss-bridge/issues))
- Discuss bugs, features, ideas or [issues](https://github.com/RSS-Bridge/rss-bridge/issues)
- Add new bridges or improve the API
- Improve the [Documentation](https://rss-bridge.github.io/rss-bridge/)
- Host an instance of RSS-Bridge for your personal use or make it available to the community :sparkling_heart:

Authors
===

We are RSS-Bridge community, a group of developers continuing the project initiated by sebsauvage, webmaster of [sebsauvage.net](https://sebsauvage.net), author of [Shaarli](https://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](https://sebsauvage.net/wiki/doku.php?id=php:zerobin).

See [CONTRIBUTORS.md](CONTRIBUTORS.md)

Licenses
===

The source code for RSS-Bridge is [Public Domain](UNLICENSE).

RSS-Bridge uses third party libraries with their own license:

  * [`Parsedown`](https://github.com/erusev/parsedown) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`PHP Simple HTML DOM Parser`](https://simplehtmldom.sourceforge.io/docs/1.9/index.html) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`php-urljoin`](https://github.com/fluffy-critter/php-urljoin) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`Laravel framework`](https://github.com/laravel/framework/) licensed under the [MIT License](https://opensource.org/licenses/MIT)

Technical notes
===

  * RSS-Bridge uses caching to prevent services from banning your server for repeatedly updating feeds. The specific cache duration can be different between bridges. Cached files are deleted automatically after 24 hours.
  * You can implement your own bridge, [following these instructions](https://rss-bridge.github.io/rss-bridge/Bridge_API/index.html).
  * You can enable debug mode to disable caching. Find more information on the [Wiki](https://rss-bridge.github.io/rss-bridge/For_Developers/Debug_mode.html)

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed feeds entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing feeds. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS or Atom feeds.

We want to share with friends, using open protocols: RSS, Atom, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have willfully destroyed.

Get your shit together: Put RSS/Atom back in.
