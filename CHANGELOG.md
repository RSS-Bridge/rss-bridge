rss-bridge Changelog
===

Alpha 0.1
===
* First tagged version.
* Includes refactoring.
* Unstable.

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
