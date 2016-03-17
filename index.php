<?php
/*
TODO :
- factorize the annotation system
- factorize to adapter : Format, Bridge, Cache (actually code is almost the same)
- implement annotation cache for entrance page
- Cache : I think logic must be change as least to avoid to reconvert object from json in FileCache case.
- add namespace to avoid futur problem ?
- see FIXME mentions in the code
- implement header('X-Cached-Version: '.date(DATE_ATOM, filemtime($cachefile)));
*/

//define('PROXY_URL', 'tcp://192.168.0.0:28');

date_default_timezone_set('UTC');
error_reporting(0);

if(file_exists("DEBUG")) {
    
    ini_set('display_errors','1'); error_reporting(E_ALL); //Report all errors
    define("DEBUG", "true");
    
}

require_once __DIR__ . '/lib/RssBridge.php';

// extensions check
if (!extension_loaded('openssl'))
	die('"openssl" extension not loaded. Please check "php.ini"');

// FIXME : beta test UA spoofing, please report any blacklisting by PHP-fopen-unfriendly websites
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:30.0) Gecko/20121202 Firefox/30.0 (rss-bridge/0.1; +https://github.com/sebsauvage/rss-bridge)');

// default whitelist
$whitelist_file = './whitelist.txt';
$whitelist_default = array(
	"BandcampBridge",
	"CryptomeBridge",
	"DansTonChatBridge",
	"DuckDuckGoBridge",
	"FacebookBridge",
	"FlickrExploreBridge",
	"GooglePlusPostBridge",
	"GoogleSearchBridge",
	"IdenticaBridge",
	"InstagramBridge",
	"OpenClassroomsBridge",
	"PinterestBridge",
	"ScmbBridge",
	"TwitterBridge",
	"WikipediaENBridge",
	"WikipediaEOBridge",
	"WikipediaFRBridge",
	"YoutubeBridge");

if (!file_exists($whitelist_file)) {
	$whitelist_selection = $whitelist_default;
	$whitelist_write = implode("\n", $whitelist_default);
	file_put_contents($whitelist_file, $whitelist_write);
}
else {
	$whitelist_selection = explode("\n", file_get_contents($whitelist_file));
}

Cache::purge();

try{

    Bridge::setDir(__DIR__ . '/bridges/');
    Format::setDir(__DIR__ . '/formats/');
    Cache::setDir(__DIR__ . '/caches/');

    if( isset($_REQUEST) && isset($_REQUEST['action']) ){
        switch($_REQUEST['action']){
            case 'display':
                if( isset($_REQUEST['bridge']) ){
                    unset($_REQUEST['action']);
                    $bridge = $_REQUEST['bridge'];
                    unset($_REQUEST['bridge']);
                    $format = $_REQUEST['format'];
                    unset($_REQUEST['format']);

			// whitelist control
			if(!Bridge::isWhitelisted($whitelist_selection, $bridge)) {
				throw new \HttpException('This bridge is not whitelisted', 401);
				die; 
			}

                    $cache = Cache::create('FileCache');

                    // Data retrieval
                    $bridge = Bridge::create($bridge);
                    if(defined("DEBUG")) {
                    } else {
                        $bridge->setCache($cache); // just add disable cache to your query to disable caching
                    }
                    $bridge->setDatas($_REQUEST);
					$bridge->loadMetadatas();
                    // Data transformation
                    try {
		                $format = Format::create($format);
		                $format
		                    ->setDatas($bridge->getDatas())
		                    ->setExtraInfos(array(
		                        'name' => $bridge->getName(),
		                        'uri' => $bridge->getURI(),
		                    ))
		                    ->display();
		            } catch(Exception $e) {

						echo "The brige has crashed. You should report this to the bridges maintainer";

		            }
                    die;
                }
                break;
        }
    }
}
catch(HttpException $e){
    header('HTTP/1.1 ' . $e->getCode() . ' ' . Http::getMessageForCode($e->getCode()));
    header('Content-Type: text/plain');
    die($e->getMessage());
}
catch(\Exception $e){
    die($e->getMessage());
}

$formats = Format::searchInformation();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Rss-bridge" />
    <title>RSS-Bridge</title>
    <link href="css/style.css" rel="stylesheet">
</head>

<body>

    <header>
        <h1>RSS-Bridge</h1>
        <h2>·Reconnecting the Web·</h2>
    </header>
	<?php
	    $activeFoundBridgeCount = 0;
		$showInactive = isset($_REQUEST['show_inactive']) && $_REQUEST['show_inactive'] == 1;
		$inactiveBridges = '';
		$bridgeList = Bridge::listBridges();
	    foreach($bridgeList as $bridgeName)
	    {
			if(Bridge::isWhitelisted($whitelist_selection, $bridgeName))
			{
				echo HTMLUtils::displayBridgeCard($bridgeName, $formats);
	            		$activeFoundBridgeCount++;
			}
			elseif ($showInactive)
			{
				// inactive bridges
				$inactiveBridges .= HTMLUtils::displayBridgeCard($bridgeName, $formats, false) . PHP_EOL;
			}
		}
		echo '<hr />' . $inactiveBridges;
	?>
    <footer>
		<?= $activeFoundBridgeCount; ?>/<?= count($bridgeList) ?> active bridges (<a href="?show_inactive=1">Show inactive</a>)<br />
        <a href="https://github.com/sebsauvage/rss-bridge">RSS-Bridge alpha 0.2 ~ Public Domain</a>
    </footer>
    </body>
</html>
