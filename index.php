<?php
/*
TODO :
- manage SSL detection because if library isn't loaded, some bridge crash !
- factorize the annotation system
- factorize to adapter : Format, Bridge, Cache (actually code is almost the same)
- implement annotation cache for entrance page
- Cache : I think logic must be change as least to avoid to reconvert object from json in FileCache case.
- add namespace to avoid futur problem ?
- see FIXME mentions in the code
- implement header('X-Cached-Version: '.date(DATE_ATOM, filemtime($cachefile)));
*/

date_default_timezone_set('UTC');
error_reporting(0);
//ini_set('display_errors','1'); error_reporting(E_ALL);  // For debugging only.

// FIXME : beta test UA spoofing, please report any blacklisting by PHP-fopen-unfriendly websites
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:30.0) Gecko/20121202 Firefox/30.0 (rss-bridge/0.1; +https://github.com/sebsauvage/rss-bridge)');
// -------



// default whitelist
$whitelist_file = './whitelist.txt';
$whitelist_default = array(
	"BandcampBridge",
	"CryptomeBridge",
	"DansTonChatBridge",
	"DuckDuckGoBridge",
	"FlickrExploreBridge",
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

// whitelist control function
function BridgeWhitelist( $whitelist, $name ) {
	if(in_array("$name", $whitelist) or in_array("$name.php", $whitelist))
		return TRUE;
	else
		return FALSE;
}

try{
    require_once __DIR__ . '/lib/RssBridge.php';

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
			if(!BridgeWhitelist($whitelist_selection, $bridge)) {
				throw new \HttpException('This bridge is not whitelisted', 401);
				die; 
			}

                    $cache = Cache::create('FileCache');

                    // Data retrieval
                    $bridge = Bridge::create($bridge);
                    if(isset($_REQUEST["disable_cache"])) {
                    } else {
                        $bridge->setCache($cache); // just add disable cache to your query to disable caching
                    }
                    $bridge->setDatas($_REQUEST);

                    // Data transformation
                    $format = Format::create($format);
                    $format
                        ->setDatas($bridge->getDatas())
                        ->setExtraInfos(array(
                            'name' => $bridge->getName(),
                            'uri' => $bridge->getURI(),
                        ))
                        ->display();
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

function getHelperButtonFormat($value, $name){
    return '<button type="submit" name="format" value="' . $value . '">' . $name . '</button>';
}

$bridges = Bridge::searchInformation();
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
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body>

    <header>
        <h1>RSS-Bridge</h1>
    </header>

        <?php foreach($bridges as $bridgeReference => $bridgeInformations): ?>
	  <?php if(BridgeWhitelist($whitelist_selection, $bridgeReference)) { ?>
            <section id="bridge-<?php echo $bridgeReference ?>" data-ref="<?php echo $bridgeReference ?>">
                <h2><?php echo isset($bridgeInformations['homepage']) ? '<a href="'.$bridgeInformations['homepage'].'">'.$bridgeInformations['name'].'</a>' : $bridgeInformations['name']  ?></h2>
                <p class="description">
                    <?php echo isset($bridgeInformations['description']) ? $bridgeInformations['description'] : 'No description provided' ?>
                </p> 

                <?php if( isset($bridgeInformations['use']) && count($bridgeInformations['use']) > 0 ): ?>
                <ol class="list-use">
                    <?php foreach($bridgeInformations['use'] as $anUseNum => $anUse): ?>
                    <li data-use="<?php echo $anUseNum ?>">
                        <form method="GET" action="?">
                            <input type="hidden" name="action" value="display" />
                            <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                            <?php foreach($anUse as $argName => $argDescription): ?>
                            <?php
                                $idArg = 'arg-' . $bridgeReference . '-' . $anUseNum . '-' . $argName;
                            ?>
                            <input id="<?php echo $idArg ?>" type="text" value="" placeholder="<?php echo $argDescription; ?>" name="<?php echo $argName ?>" />
                            <?php endforeach; ?>
                            <?php foreach( $formats as $name => $infos ): ?>
                                <?php if( isset($infos['name']) ){ echo getHelperButtonFormat($name, $infos['name']); } ?>
                            <?php endforeach; ?>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php else: ?>
                <form method="GET" action="?">
                    <input type="hidden" name="action" value="display" />
                    <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                    <?php foreach( $formats as $name => $infos ): ?>
                        <?php if( isset($infos['name']) ){ echo getHelperButtonFormat($name, $infos['name']); } ?>
                    <?php endforeach; ?>
                </form>
                <?php endif; ?>
		<?php echo isset($bridgeInformations['maintainer']) ? '<span class="maintainer">'.$bridgeInformations['maintainer'].'</span>' : '' ?>
            </section>
            <?php } endforeach; ?>
    <footer>
        <a href="https://github.com/sebsauvage/rss-bridge">RSS-Bridge</a> alpha 0.1
    </footer>  
    </body>
</html>
