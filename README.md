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

**Contributors** (sorted alphabetically):
<!--
Use this script to generate the list automatically (using the GitHub API):
./contrib/prepare_release/fetch_contributors.php
-->

* [16mhz](https://github.com/16mhz)
* [adamchainz](https://github.com/adamchainz)
* [Ahiles3005](https://github.com/Ahiles3005)
* [akirk](https://github.com/akirk)
* [Albirew](https://github.com/Albirew)
* [aledeg](https://github.com/aledeg)
* [alex73](https://github.com/alex73)
* [alexAubin](https://github.com/alexAubin)
* [Alkarex](https://github.com/Alkarex)
* [AmauryCarrade](https://github.com/AmauryCarrade)
* [arnd-s](https://github.com/arnd-s)
* [ArthurHoaro](https://github.com/ArthurHoaro)
* [Astalaseven](https://github.com/Astalaseven)
* [Astyan-42](https://github.com/Astyan-42)
* [austinhuang0131](https://github.com/austinhuang0131)
* [AxorPL](https://github.com/AxorPL)
* [ayacoo](https://github.com/ayacoo)
* [az5he6ch](https://github.com/az5he6ch)
* [b1nj](https://github.com/b1nj)
* [benasse](https://github.com/benasse)
* [Binnette](https://github.com/Binnette)
* [BoboTiG](https://github.com/BoboTiG)
* [Bockiii](https://github.com/Bockiii)
* [captn3m0](https://github.com/captn3m0)
* [chemel](https://github.com/chemel)
* [Chouchen](https://github.com/Chouchen)
* [ckiw](https://github.com/ckiw)
* [cn-tools](https://github.com/cn-tools)
* [cnlpete](https://github.com/cnlpete)
* [corenting](https://github.com/corenting)
* [couraudt](https://github.com/couraudt)
* [csisoap](https://github.com/csisoap)
* [da2x](https://github.com/da2x)
* [dabenzel](https://github.com/dabenzel)
* [Daiyousei](https://github.com/Daiyousei)
* [dawidsowa](https://github.com/dawidsowa)
* [DevonHess](https://github.com/DevonHess)
* [dhuschde](https://github.com/dhuschde)
* [disk0x](https://github.com/disk0x)
* [DJCrashdummy](https://github.com/DJCrashdummy)
* [Djuuu](https://github.com/Djuuu)
* [DnAp](https://github.com/DnAp)
* [dominik-th](https://github.com/dominik-th)
* [Draeli](https://github.com/Draeli)
* [Dreckiger-Dan](https://github.com/Dreckiger-Dan)
* [drego85](https://github.com/drego85)
* [drklee3](https://github.com/drklee3)
* [DRogueRonin](https://github.com/DRogueRonin)
* [dvikan](https://github.com/dvikan)
* [eggwhalefrog](https://github.com/eggwhalefrog)
* [em92](https://github.com/em92)
* [eMerzh](https://github.com/eMerzh)
* [EtienneM](https://github.com/EtienneM)
* [f0086](https://github.com/f0086)
* [fanch317](https://github.com/fanch317)
* [fatuuse](https://github.com/fatuuse)
* [fivefilters](https://github.com/fivefilters)
* [floviolleau](https://github.com/floviolleau)
* [fluffy-critter](https://github.com/fluffy-critter)
* [fmachen](https://github.com/fmachen)
* [Frenzie](https://github.com/Frenzie)
* [fulmeek](https://github.com/fulmeek)
* [ggiessen](https://github.com/ggiessen)
* [gileri](https://github.com/gileri)
* [Ginko-Aloe](https://github.com/Ginko-Aloe)
* [girlpunk](https://github.com/girlpunk)
* [Glandos](https://github.com/Glandos)
* [gloony](https://github.com/gloony)
* [GregThib](https://github.com/GregThib)
* [griffaurel](https://github.com/griffaurel)
* [Grummfy](https://github.com/Grummfy)
* [gsantner](https://github.com/gsantner)
* [guigot](https://github.com/guigot)
* [hollowleviathan](https://github.com/hollowleviathan)
* [hpacleb](https://github.com/hpacleb)
* [hunhejj](https://github.com/hunhejj)
* [husim0](https://github.com/husim0)
* [IceWreck](https://github.com/IceWreck)
* [imagoiq](https://github.com/imagoiq)
* [j0k3r](https://github.com/j0k3r)
* [JackNUMBER](https://github.com/JackNUMBER)
* [jacquesh](https://github.com/jacquesh)
* [jakubvalenta](https://github.com/jakubvalenta)
* [JasonGhent](https://github.com/JasonGhent)
* [jcgoette](https://github.com/jcgoette)
* [jdesgats](https://github.com/jdesgats)
* [jdigilio](https://github.com/jdigilio)
* [JeremyRand](https://github.com/JeremyRand)
* [JimDog546](https://github.com/JimDog546)
* [jNullj](https://github.com/jNullj)
* [Jocker666z](https://github.com/Jocker666z)
* [johnnygroovy](https://github.com/johnnygroovy)
* [johnpc](https://github.com/johnpc)
* [joni1993](https://github.com/joni1993)
* [jtojnar](https://github.com/jtojnar)
* [KamaleiZestri](https://github.com/KamaleiZestri)
* [kkoyung](https://github.com/kkoyung)
* [klimplant](https://github.com/klimplant)
* [KN4CK3R](https://github.com/KN4CK3R)
* [kolarcz](https://github.com/kolarcz)
* [kranack](https://github.com/kranack)
* [kraoc](https://github.com/kraoc)
* [krisu5](https://github.com/krisu5)
* [l1n](https://github.com/l1n)
* [laBecasse](https://github.com/laBecasse)
* [lagaisse](https://github.com/lagaisse)
* [lalannev](https://github.com/lalannev)
* [langfingaz](https://github.com/langfingaz)
* [lassana](https://github.com/lassana)
* [ldidry](https://github.com/ldidry)
* [Leomaradan](https://github.com/Leomaradan)
* [leyrer](https://github.com/leyrer)
* [liamka](https://github.com/liamka)
* [Limero](https://github.com/Limero)
* [LogMANOriginal](https://github.com/LogMANOriginal)
* [lorenzos](https://github.com/lorenzos)
* [lukasklinger](https://github.com/lukasklinger)
* [m0zes](https://github.com/m0zes)
* [Mar-Koeh](https://github.com/Mar-Koeh)
* [marcus-at-localhost](https://github.com/marcus-at-localhost)
* [marius8510000-bot](https://github.com/marius8510000-bot)
* [matthewseal](https://github.com/matthewseal)
* [mcbyte-it](https://github.com/mcbyte-it)
* [mdemoss](https://github.com/mdemoss)
* [melangue](https://github.com/melangue)
* [metaMMA](https://github.com/metaMMA)
* [mibe](https://github.com/mibe)
* [mickaelBert](https://github.com/mickaelBert)
* [mightymt](https://github.com/mightymt)
* [mitsukarenai](https://github.com/mitsukarenai)
* [Monocularity](https://github.com/Monocularity)
* [MonsieurPoutounours](https://github.com/MonsieurPoutounours)
* [mr-flibble](https://github.com/mr-flibble)
* [mro](https://github.com/mro)
* [mschwld](https://github.com/mschwld)
* [muekoeff](https://github.com/muekoeff)
* [mw80](https://github.com/mw80)
* [mxmehl](https://github.com/mxmehl)
* [Mynacol](https://github.com/Mynacol)
* [nel50n](https://github.com/nel50n)
* [niawag](https://github.com/niawag)
* [Niehztog](https://github.com/Niehztog)
* [NikNikYkt](https://github.com/NikNikYkt)
* [Nono-m0le](https://github.com/Nono-m0le)
* [obsiwitch](https://github.com/obsiwitch)
* [Ololbu](https://github.com/Ololbu)
* [ORelio](https://github.com/ORelio)
* [otakuf](https://github.com/otakuf)
* [Park0](https://github.com/Park0)
* [Paroleen](https://github.com/Paroleen)
* [Patricol](https://github.com/Patricol)
* [paulchen](https://github.com/paulchen)
* [PaulVayssiere](https://github.com/PaulVayssiere)
* [pellaeon](https://github.com/pellaeon)
* [PeterDaveHello](https://github.com/PeterDaveHello)
* [Peterr-K](https://github.com/Peterr-K)
* [Piranhaplant](https://github.com/Piranhaplant)
* [pirnz](https://github.com/pirnz)
* [pit-fgfjiudghdf](https://github.com/pit-fgfjiudghdf)
* [pitchoule](https://github.com/pitchoule)
* [pmaziere](https://github.com/pmaziere)
* [Pofilo](https://github.com/Pofilo)
* [prysme01](https://github.com/prysme01)
* [pubak42](https://github.com/pubak42)
* [Qluxzz](https://github.com/Qluxzz)
* [quentinus95](https://github.com/quentinus95)
* [quickwick](https://github.com/quickwick)
* [rakoo](https://github.com/rakoo)
* [RawkBob](https://github.com/RawkBob)
* [regisenguehard](https://github.com/regisenguehard)
* [Riduidel](https://github.com/Riduidel)
* [rogerdc](https://github.com/rogerdc)
* [Roliga](https://github.com/Roliga)
* [ronansalmon](https://github.com/ronansalmon)
* [rremizov](https://github.com/rremizov)
* [s0lesurviv0r](https://github.com/s0lesurviv0r)
* [sal0max](https://github.com/sal0max)
* [sebsauvage](https://github.com/sebsauvage)
* [shutosg](https://github.com/shutosg)
* [simon816](https://github.com/simon816)
* [Simounet](https://github.com/Simounet)
* [somini](https://github.com/somini)
* [SpangleLabs](https://github.com/SpangleLabs)
* [SqrtMinusOne](https://github.com/SqrtMinusOne)
* [squeek502](https://github.com/squeek502)
* [StelFux](https://github.com/StelFux)
* [stjohnjohnson](https://github.com/stjohnjohnson)
* [Stopka](https://github.com/Stopka)
* [Strubbl](https://github.com/Strubbl)
* [sublimz](https://github.com/sublimz)
* [sunchaserinfo](https://github.com/sunchaserinfo)
* [SuperSandro2000](https://github.com/SuperSandro2000)
* [sysadminstory](https://github.com/sysadminstory)
* [t0stiman](https://github.com/t0stiman)
* [tameroski](https://github.com/tameroski)
* [teromene](https://github.com/teromene)
* [tgkenney](https://github.com/tgkenney)
* [thefranke](https://github.com/thefranke)
* [TheRadialActive](https://github.com/TheRadialActive)
* [theScrabi](https://github.com/theScrabi)
* [thezeroalpha](https://github.com/thezeroalpha)
* [thibaultcouraud](https://github.com/thibaultcouraud)
* [timendum](https://github.com/timendum)
* [TitiTestScalingo](https://github.com/TitiTestScalingo)
* [tomaszkane](https://github.com/tomaszkane)
* [tomershvueli](https://github.com/tomershvueli)
* [TotalCaesar659](https://github.com/TotalCaesar659)
* [tpikonen](https://github.com/tpikonen)
* [TReKiE](https://github.com/TReKiE)
* [triatic](https://github.com/triatic)
* [User123698745](https://github.com/User123698745)
* [VerifiedJoseph](https://github.com/VerifiedJoseph)
* [vitkabele](https://github.com/vitkabele)
* [WalterBarrett](https://github.com/WalterBarrett)
* [wtuuju](https://github.com/wtuuju)
* [xurxof](https://github.com/xurxof)
* [yamanq](https://github.com/yamanq)
* [yardenac](https://github.com/yardenac)
* [ymeister](https://github.com/ymeister)
* [yue-dongchen](https://github.com/yue-dongchen)
* [ZeNairolf](https://github.com/ZeNairolf)

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
