rss-bridge
===

rss-bridge is a PHP project capable of generating ATOM feeds for websites which don't have one.

Supported sites/pages
===

 * `FlickrExplore` : [Latest interesting images](http://www.flickr.com/explore) from Flickr
 * `GoogleSearch` : Most recent results from Google Search
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

 * PHP 5.3
 * TLS lib activated in PHP config for some bridges.

 
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

Licence
===
Code is [Public Domain](LICENSE).

Included `PHP Simple HTML DOM Parser` is under the [MIT License](http://opensource.org/licenses/MIT)


Technical notes
===
  * There is a cache so that source services won't ban you even if you hammer the rss-bridge with requests. Each bridge has a different duration for the cache. The `cache` subdirectory will be automatically created. You can purge it whenever you want.
  * To implement a new rss-bridge, create a new class in `bridges` subdirectory. Look at existing bridges for examples. For items you generate in `$this->items`, only `uri` and `title` are mandatory in each item. `timestamp` and `content` are optional but recommended. Any additional key will be ignored by ATOM feed (but outputed to json).

Rant
===

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed RSS entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing RSS. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS.

We want to share with friends, using open protocols: RSS, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have wilfully destroyed.

Get your shit together: Put RSS back in.
