rss-bridge
===
[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE) [![GitHub release](https://img.shields.io/github/release/rss-bridge/rss-bridge.svg)](https://github.com/rss-bridge/rss-bridge/releases/latest) [![Debian Release](https://img.shields.io/badge/dynamic/json.svg?label=debian%20release&url=https%3A%2F%2Fsources.debian.org%2Fapi%2Fsrc%2Frss-bridge%2F&query=%24.versions%5B0%5D.version&colorB=blue)](https://tracker.debian.org/pkg/rss-bridge) [![Guix Release](https://img.shields.io/badge/guix%20release-unknown-light--gray.svg)](https://www.gnu.org/software/guix/packages/R/) [![Build Status](https://travis-ci.org/RSS-Bridge/rss-bridge.svg?branch=master)](https://travis-ci.org/RSS-Bridge/rss-bridge) [![Docker Build Status](https://img.shields.io/docker/build/rssbridge/rss-bridge.svg)](https://hub.docker.com/r/rssbridge/rss-bridge/)

RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites which don't have one. It can be used on webservers or as stand alone application in CLI mode.

Supported sites/pages (examples)
===

* `Bandcamp` : Returns last release from [bandcamp](https://bandcamp.com/) for a tag
* `Cryptome` : Returns the most recent documents from [Cryptome.org](http://cryptome.org/)
* `DansTonChat`: Most recent quotes from [danstonchat.com](http://danstonchat.com/)
* `DuckDuckGo`: Most recent results from [DuckDuckGo.com](https://duckduckgo.com/)
* `Facebook` : Returns the latest posts on a page or profile on [Facebook](https://facebook.com/)
* `FlickrExplore` : [Latest interesting images](http://www.flickr.com/explore) from Flickr
* `GooglePlus` : Most recent posts of user timeline
* `GoogleSearch` : Most recent results from Google Search
* `Identi.ca` : Identica user timeline (Should be compatible with other Pump.io instances)
* `Instagram`: Most recent photos from an Instagram user
* `OpenClassrooms`: Lastest tutorials from [fr.openclassrooms.com](http://fr.openclassrooms.com/)
* `Pinterest`: Most recent photos from user or search
* `ScmbBridge`: Newest stories from [secouchermoinsbete.fr](http://secouchermoinsbete.fr/)
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

You can extend RSS-Bridge with your own format, using the [Format API](https://github.com/RSS-Bridge/rss-bridge/wiki/Format-API)!

Screenshot
===

Welcome screen:

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_rss-bridge_welcome.png)

***

RSS-Bridge hashtag (#rss-bridge) search on Twitter, in Atom format (as displayed by Firefox):

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_twitterbridge_atom.png)

Requirements
===

RSS-Bridge requires PHP 5.6 or higher with following extensions enabled:

  - [`openssl`](https://secure.php.net/manual/en/book.openssl.php)
  - [`libxml`](https://secure.php.net/manual/en/book.libxml.php)
  - [`mbstring`](https://secure.php.net/manual/en/book.mbstring.php)
  - [`simplexml`](https://secure.php.net/manual/en/book.simplexml.php)
  - [`curl`](https://secure.php.net/manual/en/book.curl.php)
  - [`json`](https://secure.php.net/manual/en/book.json.php)

Find more information on our [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)

Enable / Disable bridges
===

RSS-Bridge allows you to take full control over which bridges are displayed to the user. That way you can host your own RSS-Bridge service with your favorite collection of bridges!

Find more information on the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitelisting)

**Notice**: By default RSS-Bridge will only show a small subset of bridges. Make sure to read up on [whitelisting](https://github.com/RSS-Bridge/rss-bridge/wiki/Whitelisting) to unlock the full potential of RSS-Bridge!

Deploy
===

Thanks to the community, hosting your own instance of RSS-Bridge is as easy as clicking a button!

[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
[![Deploy to Docker Cloud](https://files.cloud.docker.com/images/deploy-to-dockercloud.svg)](https://cloud.docker.com/stack/deploy/?repo=https://github.com/rss-bridge/rss-bridge)

Getting involved
===

There are many ways for you to getting involved with RSS-Bridge. Here are a few things:

- Share RSS-Bridge with your friends (Twitter, Facebook, ..._you name it_...)
- Report broken bridges or bugs by opening [Issues](https://github.com/RSS-Bridge/rss-bridge/issues) on GitHub
- Request new features or suggest ideas (via [Issues](https://github.com/RSS-Bridge/rss-bridge/issues))
- Discuss bugs, features, ideas or [issues](https://github.com/RSS-Bridge/rss-bridge/issues)
- Add new bridges or improve the API
- Improve the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki)
- Host an instance of RSS-Bridge for your personal use or make it available to the community :sparkling_heart:

Authors
===

We are RSS-Bridge community, a group of developers continuing the project initiated by sebsauvage, webmaster of [sebsauvage.net](http://sebsauvage.net), author of [Shaarli](http://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](http://sebsauvage.net/wiki/doku.php?id=php:zerobin).

**Contributors** (sorted alphabetically):
<!--
Use this script to generate the list automatically (using the GitHub API):
https://gist.github.com/LogMANOriginal/da00cd1e5f0ca31cef8e193509b17fd8
-->

  * [16mhz](https://api.github.com/users/16mhz)
  * [Ahiles3005](https://api.github.com/users/Ahiles3005)
  * [Albirew](https://api.github.com/users/Albirew)
  * [AmauryCarrade](https://api.github.com/users/AmauryCarrade)
  * [ArthurHoaro](https://api.github.com/users/ArthurHoaro)
  * [Astalaseven](https://api.github.com/users/Astalaseven)
  * [Astyan-42](https://api.github.com/users/Astyan-42)
  * [BoboTiG](https://api.github.com/users/BoboTiG)
  * [Daiyousei](https://api.github.com/users/Daiyousei)
  * [Djuuu](https://api.github.com/users/Djuuu)
  * [Draeli](https://api.github.com/users/Draeli)
  * [EtienneM](https://api.github.com/users/EtienneM)
  * [Frenzie](https://api.github.com/users/Frenzie)
  * [Ginko-Aloe](https://api.github.com/users/Ginko-Aloe)
  * [Glandos](https://api.github.com/users/Glandos)
  * [GregThib](https://api.github.com/users/GregThib)
  * [Grummfy](https://api.github.com/users/Grummfy)
  * [JackNUMBER](https://api.github.com/users/JackNUMBER)
  * [JeremyRand](https://api.github.com/users/JeremyRand)
  * [Jocker666z](https://api.github.com/users/Jocker666z)
  * [LogMANOriginal](https://api.github.com/users/LogMANOriginal)
  * [MonsieurPoutounours](https://api.github.com/users/MonsieurPoutounours)
  * [ORelio](https://api.github.com/users/ORelio)
  * [PaulVayssiere](https://api.github.com/users/PaulVayssiere)
  * [Piranhaplant](https://api.github.com/users/Piranhaplant)
  * [Riduidel](https://api.github.com/users/Riduidel)
  * [Strubbl](https://api.github.com/users/Strubbl)
  * [TheRadialActive](https://api.github.com/users/TheRadialActive)
  * [TwizzyDizzy](https://api.github.com/users/TwizzyDizzy)
  * [WalterBarrett](https://api.github.com/users/WalterBarrett)
  * [ZeNairolf](https://api.github.com/users/ZeNairolf)
  * [adamchainz](https://api.github.com/users/adamchainz)
  * [aledeg](https://api.github.com/users/aledeg)
  * [alexAubin](https://api.github.com/users/alexAubin)
  * [az5he6ch](https://api.github.com/users/az5he6ch)
  * [b1nj](https://api.github.com/users/b1nj)
  * [benasse](https://api.github.com/users/benasse)
  * [captn3m0](https://api.github.com/users/captn3m0)
  * [chemel](https://api.github.com/users/chemel)
  * [ckiw](https://api.github.com/users/ckiw)
  * [cnlpete](https://api.github.com/users/cnlpete)
  * [corenting](https://api.github.com/users/corenting)
  * [da2x](https://api.github.com/users/da2x)
  * [eMerzh](https://api.github.com/users/eMerzh)
  * [em92](https://api.github.com/users/em92)
  * [griffaurel](https://api.github.com/users/griffaurel)
  * [hunhejj](https://api.github.com/users/hunhejj)
  * [j0k3r](https://api.github.com/users/j0k3r)
  * [jdigilio](https://api.github.com/users/jdigilio)
  * [kranack](https://api.github.com/users/kranack)
  * [kraoc](https://api.github.com/users/kraoc)
  * [laBecasse](https://api.github.com/users/laBecasse)
  * [lagaisse](https://api.github.com/users/lagaisse)
  * [lalannev](https://api.github.com/users/lalannev)
  * [ldidry](https://api.github.com/users/ldidry)
  * [m0zes](https://api.github.com/users/m0zes)
  * [matthewseal](https://api.github.com/users/matthewseal)
  * [mcbyte-it](https://api.github.com/users/mcbyte-it)
  * [mdemoss](https://api.github.com/users/mdemoss)
  * [melangue](https://api.github.com/users/melangue)
  * [metaMMA](https://api.github.com/users/metaMMA)
  * [mickael-bertrand](https://api.github.com/users/mickael-bertrand)
  * [mitsukarenai](https://api.github.com/users/mitsukarenai)
  * [mro](https://api.github.com/users/mro)
  * [mxmehl](https://api.github.com/users/mxmehl)
  * [nel50n](https://api.github.com/users/nel50n)
  * [niawag](https://api.github.com/users/niawag)
  * [pellaeon](https://api.github.com/users/pellaeon)
  * [pit-fgfjiudghdf](https://api.github.com/users/pit-fgfjiudghdf)
  * [pitchoule](https://api.github.com/users/pitchoule)
  * [pmaziere](https://api.github.com/users/pmaziere)
  * [polo2ro](https://api.github.com/users/polo2ro)
  * [prysme01](https://api.github.com/users/prysme01)
  * [quentinus95](https://api.github.com/users/quentinus95)
  * [qwertygc](https://api.github.com/users/qwertygc)
  * [regisenguehard](https://api.github.com/users/regisenguehard)
  * [rogerdc](https://api.github.com/users/rogerdc)
  * [sebsauvage](https://api.github.com/users/sebsauvage)
  * [sublimz](https://api.github.com/users/sublimz)
  * [sysadminstory](https://api.github.com/users/sysadminstory)
  * [tameroski](https://api.github.com/users/tameroski)
  * [teromene](https://api.github.com/users/teromene)
  * [triatic](https://api.github.com/users/triatic)
  * [wtuuju](https://api.github.com/users/wtuuju)

Licenses
===

The source code for RSS-Bridge is [Public Domain](UNLICENSE).

RSS-Bridge uses third party libraries with their own license:

  * [`PHP Simple HTML DOM Parser`](http://simplehtmldom.sourceforge.net/) licensed under the [MIT License](http://opensource.org/licenses/MIT)
  * [`php-urljoin`](https://github.com/fluffy-critter/php-urljoin) licensed under the [MIT License](http://opensource.org/licenses/MIT)

Technical notes
===

  * RSS-Bridge uses caching to prevent services from banning your server for repeatedly updating feeds. The specific cache duration can be different between bridges. Cached files are deleted automatically after 24 hours.
  * You can implement your own bridge, [following these instructions](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API).
  * You can enable debug mode to disable caching. Find more information on the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki/Debug-mode)

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed feeds entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing feeds. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS or Atom feeds.

We want to share with friends, using open protocols: RSS, Atom, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have wilfully destroyed.

Get your shit together: Put RSS/Atom back in.
