rss-bridge
===

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
 * `WikipediaENLatest`: highlighted articles from Wikipedia in English
 * `WikipediaFRLatest`: highlighted articles from Wikipedia in French
 * `WikipediaEOLatest`: highlighted articles from Wikipedia in Esperanto
 * `Bandcamp` : Returns last release from [bandcamp](https://bandcamp.com/) for a tag
 * `ThePirateBay` : Returns the newest indexed torrents from [The Pirate Bay](https://thepiratebay.se/) with keywords
 * `Facebook` : Returns the latest posts on a page or profile on [Facebook](https://facebook.com/)

Plus [many other bridges](bridges/) to enable, thanks to the community

Output format
===
Output format can take several forms:

 * `Atom` : ATOM Feed, for use in RSS/Feed readers
 * `Json` : Json, for consumption by other applications.
 * `Html` : Simple html page.
 * `Plaintext` : raw text (php object, as returned by print_r)
   
Screenshot
===

Welcome screen:

![Screenshot](http://sebsauvage.net/galerie/photos/Bordel/rss-bridge-screenshot-3.png)
   
Minecraft hashtag (#Minecraft) search on Twitter, in ATOM format (as displayed by Firefox):

![Screenshot](http://sebsauvage.net/galerie/photos/Bordel/rss-bridge-screenshot-2-twitter-hashtag.png)
   
Requirements
===

 * PHP 5.4
 * `openssl` extension enabled in PHP config (`php.ini`)

Enabling/Disabling bridges
===

By default, the script creates `whitelist.txt` and adds the main bridges (see above). `whitelist.txt` is ignored by git, you can edit it:
 * to enable extra bridges (one bridge per line)
 * to disable main bridges (remove the line)

New bridges are disabled by default, so make sure to check regularly what's new and whitelist what you want !
 
Author
===
I'm sebsauvage, webmaster of [sebsauvage.net](http://sebsauvage.net), author of [Shaarli](http://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](http://sebsauvage.net/wiki/doku.php?id=php:zerobin).

Patch/contributors :

 * Yves ASTIER ([Draeli](https://github.com/Draeli)) : PHP optimizations, fixes, dynamic brigde/format list with all stuff behind and extend cache system. Mail : contact@yves-astier.com
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

License
===
Code is [Public Domain](UNLICENSE).

Including `PHP Simple HTML DOM Parser` under the [MIT License](http://opensource.org/licenses/MIT)


Technical notes
===
  * There is a cache so that source services won't ban you even if you hammer the rss-bridge with requests. Each bridge has a different duration for the cache. The `cache` subdirectory will be automatically created. You can purge it whenever you want.
  * To implement a new rss-bridge, create a new class in `bridges` subdirectory. Look at existing bridges for examples and the guidelines below. For items you generate in `$this->items`, only `uri` and `title` are mandatory in each item. `timestamp` and `content` are optional but recommended. Any additional key will be ignored by ATOM feed (but outputed to json).

### Bridge guidelines

  * metatags: `@name` {Name of service}, `@homepage` {URL to homepage}, `@description`, `@update` {YYYY-MM-DD}, `@maintainer` {Github username or nickname}
  * scripts (eg. Javascript) must be stripped out. Make good use of `strip_tags()` and `preg_replace()`
  * bridge must present data within 8 seconds (adjust iterators accordingly)
  * cache timeout must be fine-tuned so that each refresh can provide 1 or 2 new elements on busy periods
  * `<audio>` and `<video>` must not autoplay. Seriously.
  * do everything you can to extract valid timestamps. Translate formats, use API, exploit sitemap, whatever. Free the data!
  * don't create duplicates. If the website runs on WordPress, use the generic WordPress bridge if possible.
  * maintain efficient and well-commented code :wink:

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed RSS entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing RSS. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS.

We want to share with friends, using open protocols: RSS, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have wilfully destroyed.

Get your shit together: Put RSS back in.
