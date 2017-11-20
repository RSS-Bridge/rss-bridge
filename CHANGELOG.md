rss-bridge Changelog
===

RSS-Bridge 2017-08-19
==

## General changes
* whitelist: Do case-insensitive whitelist matching
* [FeedExpander] Fix Serialization of 'SimpleXMLElement' is not allowed
* [FeedExpander] Remove whitespace from source content
* [index] Add GET parameter 'q' for search queries
  - **Example**: You can now add `&q=Twitter` to load into the search field
* [index] Check permissions for cache folder and whitelist file
* [index] Show bridge options when loading with URL fragment
  - **Example**: You can now add `#bridge-Twitter` to load the card with all
parameters visible
* [style] Center search cursor and hide placeholder
* [validation] Fix error on undefined optional numeric value

## Modified bridges
* [DanbooruBridge] Allow descendant classes to override tag collection
* [DribbbleBridge] Add dribble bridge listing last dribble popular shots (#558)
* [FacebookBridge] Fix &amp; in URLs
* [GelbooruBridge] Fix bridge not getting tags correctly
* [GoComicsBridge] Fix for page structure changes (#568)
* [LeBonCoinBridge] Fix bridge is marked executable
* [LWNprevBridge] Fix everchanging url
* [YoutubeBridge] Fix error on certain keywords
* [YoutubeBridge] Fix issues loading playlists

## Removed bridges
* VineBridge

RSS-Bridge 2017-08-03
==

## Important changes
* RSS-Bridge now has [contribution guidelines](CONTRIBUTING.md)
* [phpcs rules](phpcs.xml) follow the [contribution guidelines](CONTRIBUTING.md)

## General changes
* Added a search bar to make searching for bridges easier
* Added user friendly error page for when a bridge fails
* Added caching of extraInfos (name, uri)
* Added an indicator to warn for bridges using HTTP instead of HTTPS
* Various bug fixes and improvements

## Modified bridges
* AllocineFRBridge] Update Faux Raccord link
* [DanbooruBridge] Fix broken URI
* [DuckDuckGoBridge] Disable DuckDuckGo redirects so that the links returned are correct.
* [FacebookBridge] Add option to hide posts with facebook videos
* [FacebookBridge] Add requester languages to HTTP header
* [FacebookBridge] Handle summary posts
* [FacebookBridge] Replace 'novideo' with 'media_type'
* [FilterBridge] Initial implementation of basic title permit and block
* [FlickrTagBridge] Fix and improve bridge by using the FlickrExploreBridge approach
* [GooglePlusPostBridge] Autofix user names
* [GooglePlusPostBridge] Fix bridge implementation
* [GooglePlusPostBridge] Fix content loading
* [InstagramBridge] Add option to filter for videos and pictures
* [LWNprevBridge] full rewrite
* [MangareaderBridge] Fix double forward slashes
* [NasaApodBridge] Use HTTPS instead of HTTP
* [PinterestBridge] Fix checkbox not working
* [PinterestBridge] Fix implementation after DOM changes
* [RTBFBridge] Update URI
* [SexactuBridge] Fix URI and timestamp
* [SexactuBridge] Use most modern version of bridge api and cached pages (#504)
* [ShanaprojectBridge] Don't throw error if timestamp is missing
* [TwitterBridge] Add option to hide retweets
* [TwitterBridge] Avoid empty content caused by new login policy
* [TwitterBridge] Fix double slashes in URI
* [TwitterBridge] Fix missing spaces
* [TwitterBridge] Fix title includes anchors in plaintext format
* [TwitterBridge] ignore promoted tweets
* [TwitterBridge] Optimize returned image sizes
* [TwitterBridge] Show quotes and pictures
* [WebfailBridge] Properly handle gifs (DOM changed)
* [YoutubeBridge] Improve readability of feed contents
* [YoutubeBridge] Improve URL handling in video descriptions

## New bridges
* AmazonBridge
* DiceBridge
* EtsyBridge
* FB2Bridge
* FilterBridge
* FlickrBridge
* GithubSearchBridge
* GoComicsBridge
* KATBridge
* KernelBugTrackerBridge
* MixCloudBridge
* MoinMoinBridge
* RainbowSixSiegeBridge
* SteamBridge
* TheTVDBBridge
* Torrent9Bridge
* UsbekEtRicaBridge
* WikiLeaksBridge
* WordPressPluginUpdateBridge

Alpha 0.2
===

## Important changes
* RSS-Bridge has been [UNLICENSED](UNLICENSE)
* RSS-Bridge is now a community-managed project on [GitHub](https://github.com/rss-bridge/rss-bridge)
* RSS-Bridge now has a [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)
* RSS-Bridge now supports [Travis-CI](https://travis-ci.org)

## General changes
* Added [CHANGELOG](CHANGELOG.md) (this file)
* Added [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net) to [vendor](vendor/simplehtmldom/)
* Added cache purging function (cache will be force-purged after 24 hours or as defined by bridge)
* Added new format [MrssFormat](formats/MrssFormat.php)
* Added parameter `author` - for display of the feed author name - to all formats
* Added new abstraction of the BridgeInterface:
  - [FeedExpander](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API)
* Added optional support for proxy usage on each individual bridge
* Added support for [custom bridge parameter](https://github.com/RSS-Bridge/rss-bridge/wiki/BridgeAbstract#format-specifications) (text, number, list, checkbox)
* Changed design of the welcome screen
* Changed design of HtmlFormat
* Changed behavior of debug mode:
  - Enable debug mode by placing a file called "DEBUG" in the root folder
  - Debug mode automatically disables cache file loading
* Changed implementation of bridges - see [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)
  - Changed comment-style metadata to constants
  - Added support for multiple utilizations per bridge
  - Changed the parameter loading algorithm to be loaded by RSS-Bridge core
* Improved checks for PHP version, configuration and extensions
* Many bug fixes

## Modified Bridges
* FlickrExploreBridge
* GoogleSearchBridge
* TwitterBridge

## New Bridges
* ABCTabsBridge
* AcrimedBridge
* AllocineFRBridge
* AnimeUltimeBridge
* Arte7Bridge
* AskfmBridge
* BandcampBridge
* BastaBridge
* BlaguesDeMerdeBridge
* BooruprojectBridge
* CADBridge
* CNETBridge
* CastorusBridge
* CollegeDeFranceBridge
* CommonDreamsBridge
* CopieDoubleBridge
* CourrierInternationalBridge
* CpasbienBridge
* CryptomeBridge
* DailymotionBridge
* DanbooruBridge
* DansTonChatBridge
* DauphineLibereBridge
* DemoBridge
* DeveloppezDotComBridge
* DilbertBridge
* DollbooruBridge
* DuckDuckGoBridge
* EZTVBridge
* EliteDangerousGalnetBridge
* ElsevierBridge
* EstCeQuonMetEnProdBridge
* FacebookBridge
* FierPandaBridge
* FlickrTagBridge
* FootitoBridge
* FourchanBridge
* FuturaSciencesBridge
* GBAtempBridge
* GelbooruBridge
* GiphyBridge
* GithubIssueBridge
* GizmodoBridge
* GooglePlusPostBridge
* HDWallpapersBridge
* HentaiHavenBridge
* IdenticaBridge
* InstagramBridge
* IsoHuntBridge
* JapanExpoBridge
* KonachanBridge
* KoreusBridge
* KununuBridge
* LWNprevBridge
* LeBonCoinBridge
* LegifranceJOBridge
* LeMondeInformatiqueBridge
* LesJoiesDuCodeBridge
* LichessBridge
* LinkedInCompanyBridge
* LolibooruBridge
* MangareaderBridge
* MilbooruBridge
* MoebooruBridge
* MondeDiploBridge
* MsnMondeBridge
* MspabooruBridge
* NasaApodBridge
* NeuviemeArtBridge
* NextInpactBridge
* NextgovBridge
* NiceMatinBridge
* NovelUpdatesBridge
* OpenClassroomsBridge
* ParuVenduImmoBridge
* PickyWallpapersBridge
* PinterestBridge
* PlanetLibreBridge
* RTBFBridge
* ReadComicsBridge
* Releases3DSBridge
* ReporterreBridge
* Rue89Bridge
* Rule34Bridge
* Rule34pahealBridge
* SafebooruBridge
* SakugabooruBridge
* ScmbBridge
* ScoopItBridge
* SensCritiqueBridge
* SexactuBridge
* ShanaprojectBridge
* Shimmie2Bridge
* SoundcloudBridge
* StripeAPIChangeLogBridge
* SuperbWallpapersBridge
* T411Bridge
* TagBoardBridge
* TbibBridge
* TheCodingLoveBridge
* TheHackerNewsBridge
* ThePirateBayBridge
* UnsplashBridge
* ViadeoCompanyBridge
* VineBridge
* VkBridge
* WallpaperStopBridge
* WebfailBridge
* WeLiveSecurityBridge
* WhydBridge
* WikipediaBridge
* WordPressBridge
* WorldOfTanksBridge
* XbooruBridge
* YandereBridge
* YoutubeBridge
* ZDNetBridge

Alpha 0.1
===
* First tagged version.
* Includes refactoring.
* Unstable.