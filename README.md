rss-bridge
===
[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE) [![Build Status](https://travis-ci.org/RSS-Bridge/rss-bridge.svg?branch=master)](https://travis-ci.org/RSS-Bridge/rss-bridge)

rss-bridge is a PHP project capable of generating ATOM feeds for websites which don't have one.

Supported sites/pages (main)
===

 * `FlickrExplore` : [Latest interesting images](http://www.flickr.com/explore) from Flickr
 * `GoogleSearch` : Most recent results from Google Search
 * `GooglePlus` : Most recent posts of user timeline
 * `Twitter` : Return keyword/hashtag search or user timeline
 * `Identi.ca` : Identica user timeline (Should be compatible with other Pump.io instances)
 * `YouTube` : YouTube user channel, playlist or search
 * `Cryptome` : Returns the most recent documents from [Cryptome.org](http://cryptome.org/)
 * `DansTonChat`: Most recent quotes from [danstonchat.com](http://danstonchat.com/)
 * `DuckDuckGo`: Most recent results from [DuckDuckGo.com](https://duckduckgo.com/)
 * `Instagram`: Most recent photos from an Instagram user
 * `OpenClassrooms`: Lastest tutorials from [fr.openclassrooms.com](http://fr.openclassrooms.com/)
 * `Pinterest`: Most recent photos from user or search
 * `ScmbBridge`: Newest stories from [secouchermoinsbete.fr](http://secouchermoinsbete.fr/)
 * `Wikipedia`: highlighted articles from [Wikipedia](https://wikipedia.org/) in English, German, French or Esperanto
 * `Bandcamp` : Returns last release from [bandcamp](https://bandcamp.com/) for a tag
 * `ThePirateBay` : Returns the newest indexed torrents from [The Pirate Bay](https://thepiratebay.se/) with keywords
 * `Facebook` : Returns the latest posts on a page or profile on [Facebook](https://facebook.com/)

Plus [many other bridges](bridges/) to enable, thanks to the community

Output format
===
Output format can take several forms:

 * `Atom` : ATOM Feed, for use in RSS/Feed readers
 * `Mrss` : MRSS Feed, for use in RSS/Feed readers
 * `Json` : Json, for consumption by other applications.
 * `Html` : Simple html page.
 * `Plaintext` : raw text (php object, as returned by print_r)
   
Screenshot
===

Welcome screen:

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_rss-bridge_welcome.png)
   
RSS-Bridge hashtag (#rss-bridge) search on Twitter, in ATOM format (as displayed by Firefox):

![Screenshot](https://github.com/RSS-Bridge/rss-bridge/wiki/images/screenshot_twitterbridge_atom.png)
   
Requirements
===

 * PHP 5.6, e.g. `AddHandler application/x-httpd-php56 .php` in `.htaccess`
 * `openssl` extension enabled in PHP config (`php.ini`)
 * `allow_url_fopen=1` in `php.ini`

Enabling/Disabling bridges
===

By default, the script creates `whitelist.txt` and adds the main bridges (see above). `whitelist.txt` is ignored by git, you can edit it:
 * to enable extra bridges (one bridge per line)
 * to disable main bridges (remove the line)
 * to enable all bridges (just one wildcard `*` as file content)

New bridges are disabled by default, so make sure to check regularly what's new and whitelist what you want!

Deploy
===
[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
 
Authors
===
We are RSS Bridge Community, a group of developers continuing the project initiated by sebsauvage, webmaster of [sebsauvage.net](http://sebsauvage.net), author of [Shaarli](http://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](http://sebsauvage.net/wiki/doku.php?id=php:zerobin).

Patch/contributors :

 * Yves ASTIER ([Draeli](https://github.com/Draeli)) : PHP optimizations, fixes, dynamic brigde/format list with all stuff behind and extend cache system. Mail : contact /at\ yves-astier.com
 * [Mitsukarenai](https://github.com/Mitsukarenai) : Initial inspiration, collaborator
 * [ArthurHoaro](https://github.com/ArthurHoaro)
 * [BoboTiG](https://github.com/BoboTiG)
 * [Astalaseven](https://github.com/Astalaseven)
 * [qwertygc](https://github.com/qwertygc)
 * [Djuuu](https://github.com/Djuuu)
 * [Anadrark](https://github.com/Anadrark])
 * [Grummfy](https://github.com/Grummfy)
 * [Polopollo](https://github.com/Polopollo)
 * [16mhz](https://github.com/16mhz)
 * [kranack](https://github.com/kranack)
 * [logmanoriginal](https://github.com/logmanoriginal)
 * [polo2ro](https://github.com/polo2ro)
 * [Riduidel](https://github.com/Riduidel)
 * [superbaillot.net](http://superbaillot.net/)
 * [vinzv](https://github.com/vinzv)
 * [teromene](https://github.com/teromene)
 * [nel50n](https://github.com/nel50n)
 * [nyutag](https://github.com/nyutag)
 * [ORelio](https://github.com/ORelio)
 * [Pitchoule](https://github.com/Pitchoule)
 * [pit-fgfjiudghdf](https://github.com/pit-fgfjiudghdf)
 * [aledeg](https://github.com/aledeg)
 * [alexAubin](https://github.com/alexAubin)
 * [cnlpete](https://github.com/cnlpete)
 * [corenting](https://github.com/corenting)
 * [Daiyousei](https://github.com/Daiyousei)
 * [erwang](https://github.com/erwang)
 * [gsurrel](https://github.com/gsurrel)
 * [kraoc](https://github.com/kraoc)
 * [lagaisse](https://github.com/lagaisse)
 * [az5he6ch](https://github.com/az5he6ch)
 * [niawag](https://github.com/niawag)
 * [JeremyRand](https://github.com/JeremyRand)
 * [mro](https://github.com/mro)

Licenses
===
Code is [Public Domain](UNLICENSE).

Including `PHP Simple HTML DOM Parser` under the [MIT License](http://opensource.org/licenses/MIT)


Technical notes
===
  * There is a cache so that source services won't ban you even if you hammer the rss-bridge with requests. Each bridge can have a different duration for the cache. The `cache` subdirectory will be automatically created and cached objects older than 24 hours get purged.
  * To implement a new Bridge, [follow the specifications](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API) and take a look at existing Bridges for examples.
  * To enable debug mode (disabling cache and enabling error reporting), create an empty file named `DEBUG` in the root directory (next to `index.php`).
  * For more information refer to the [Wiki](https://github.com/RSS-Bridge/rss-bridge/wiki)

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed feeds entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing feeds. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS or ATOM feeds.

We want to share with friends, using open protocols: RSS, ATOM, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have wilfully destroyed.

Get your shit together: Put RSS/ATOM back in.
