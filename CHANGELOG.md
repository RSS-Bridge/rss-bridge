rss-bridge Changelog
===

Alpha 0.1
===
* First tagged version.
* Includes refactoring.
* Unstable.

Alpha 0.2 (current development version)
===

## Important changes
* RSS-Bridge has been [UNLICENSED](UNLICENSE)
* RSS-Bridge is now a community-managed project on [GitHub](https://github.com/rss-bridge/rss-bridge)
* RSS-Bridge now has a [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)

## General changes
* Added [CHANGELOG](CHANGELOG.md) (this file)
* Added [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net) to [vendor](vendor/simplehtmldom/)
* Added cache purging function (cache will be force-purged after 24 hours or as defined by bridge)
* Added new format [MrssFormat](formats/MrssFormat.php)
* Added parameter `author` - for display of the feed author name - to all formats
* Added new abstractions of the BridgeInterface:
  - [HttpCachingBridgeAbstract](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API)
  - [RssExpander](https://github.com/RSS-Bridge/rss-bridge/wiki/Bridge-API)
* Added optional support for proxy usage on each individual bridge
* Added support for [custom bridge parameter](https://github.com/RSS-Bridge/rss-bridge/wiki/BridgeAbstract#format-specifications) (text, number, list, checkbox)
* Changed design of the welcome screen
* Changed design of HtmlFormat
* Changed behavior of debug mode:
  - Enable debug mode by placing a file called "DEBUG" in the root folder
  - Debug mode automatically disables caching
* Changed implementation of bridges - see [Wiki](https://github.com/rss-bridge/rss-bridge/wiki)
  - Changed comment-style metadata to public function [`loadMetadatas`](https://github.com/RSS-Bridge/rss-bridge/wiki/BridgeAbstract#the-loadmetadatas-function)
  - Added support for multiple utilizations per bridge
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
* ArstechnicaBridge
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
* Freenews
* FuturaSciencesBridge
* GBAtempBridge
* Gawker
* GelbooruBridge
* GiphyBridge
* GithubIssueBridge
* GitlabCommitsBridge
* GizmodoFRBridge
* GooglePlusPostBridge
* GuruMedBridge
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
* LeJournalDuGeekBridge
* LeMondeInformatiqueBridge
* Les400Culs
* LesJoiesDuCodeBridge
* LichessBridge
* LinkedInCompany
* LolibooruBridge
* MangareaderBridge
* MilbooruBridge
* MondeDiploBridge
* MsnMondeBridge
* MspabooruBridge
* NakedSecurityBridge
* NasaApodBridge
* NeuviemeArtBridge
* NextInpactBridge
* NextgovBridge
* NiceMatinBridge
* NovelUpdatesBridge
* NumeramaBridge
* OpenClassroomsBridge
* ParuVenduImmoBridge
* PickyWallpapersBridge
* PinterestBridge
* PlanetLibreBridge
* ProjectMGameBridge
* RTBFBridge
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
* Sexactu
* ShanaprojectBridge
* SiliconBridge
* SoundcloudBridge
* StripeAPIChangeLogBridge
* SuperbWallpapersBridge
* T411Bridge
* TagBoardBridge
* TbibBridge
* TheCodingLoveBridge
* TheHackerNewsBridge
* TheOatMealBridge
* ThePirateBayBridge
* TwitchApiBridge
* UnsplashBridge
* ViadeoCompany
* VineBridge
* VkBridge
* WallpaperStopBridge
* WeLiveSecurityBridge
* WhydBridge
* WikipediaBridge
* WordPressBridge
* WorldOfTanks
* XbooruBridge
* YandereBridge
* YoutubeBridge
* ZDNetBridge
* ZatazBridge
* ZoneTelechargementBridge
