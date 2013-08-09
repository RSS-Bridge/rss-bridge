rss-bridge
===

rss-bridge is a collection of independant php scripts capable of generating ATOM feed for specific pages which don't have one.

Supported sites/pages
===

 * `rss-bridge-flickr-explore.php` : [Latest interesting images](http://www.flickr.com/explore) from Flickr.
 * `rss-bridge-googlesearch.php` : Most recent results from Google Search. Parameters:
   * q=keyword : Keyword search.
 * `rss-bridge-twitter.php` : Twitter. Parameters:
   * q=keyword : Keyword search.
   * u=username : Get user timeline.

Output format
===
Output format can be used in any rss-bridge:

 * `format=atom` (default): ATOM Feed.
 * `format=json` : jSon
 * `format=html` : html page
 * `format=plaintext` : raw text (php object, as returned by print_r)

If format is not specified, ATOM format will be used.

Examples
===
 * `rss-bridge-twitter.php?u=Dinnerbone` : Get Dinnerbone (Minecraft developer) timeline, in ATOM format.
 * `rss-bridge-twitter.php?q=minecraft&format=html` : Everything Minecraft from Twitter, in html format.
 * `rss-bridge-flickr-explore.php` : Latest interesting images from Flickr, in ATOM format.

   
Requirements
===

 * php 5.3
 * [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/)
 
Author
===
I'm sebsauvage, webmaster of [sebsauvage.net](http://sebsauvage.net), author of [Shaarli](http://sebsauvage.net/wiki/doku.php?id=php:shaarli) and [ZeroBin](http://sebsauvage.net/wiki/doku.php?id=php:zerobin).

Thanks to [Mitsukarenai](https://github.com/Mitsukarenai) for the inspiration.

Patch :
- Yves ASTIER (Draeli) : PHP optimizations, minor fixes, dynamic brigde list with all stuff behind

Licence
===
Code is public domain.


Technical notes
===
  * There is a cache so that source services won't ban you even if you hammer the rss-bridge with requests. Each bridge has a different duration for the cache. The `cache` subdirectory will be automatically created. You can purge it whenever you want.
  * To implement a new rss-bridge, import `rss-bridge-lib.php` and subclass `RssBridgeAbstractClass`. Look at existing bridges for examples. For items you generate in `$this->items`, only `uri` and `title` are mandatory in each item. `timestamp` and `content` are optional but recommended. Any additional key will be ignored by ATOM feed (but outputed to jSon).

Rant
===

*Dear so-called "social" websites.*

Your catchword is �share�, but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed RSS entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing RSS. You're happy to have customers create content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS.

We want to share with friends, using open protocols: RSS, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API forced-feeded to them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges your have wilfully destroyed.

Get your shit together: Put RSS back in.