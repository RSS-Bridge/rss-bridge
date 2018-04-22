<?php
/*
TODO :
- factorize the annotation system
- factorize to adapter : Format, Bridge, Cache(actually code is almost the same)
- implement annotation cache for entrance page
- Cache : I think logic must be change as least to avoid to reconvert object from json in FileCache case.
- add namespace to avoid futur problem ?
- see FIXME mentions in the code
- implement header('X-Cached-Version: '.date(DATE_ATOM, filemtime($cachefile)));
*/

// Defines the minimum required PHP version for RSS-Bridge
define('PHP_VERSION_REQUIRED', '5.6.0');

//define('PROXY_URL', 'tcp://192.168.0.0:28');
// Set to true if you allow users to disable proxy usage for specific bridges
define('PROXY_BYBRIDGE', false);
// Comment this line or keep PROXY_NAME empty to display PROXY_URL instead
define('PROXY_NAME', 'Hidden Proxy Name');

// Allows the operator to specify custom cache timeouts via '&_cache_timeout=3600'
// true: enabled, false: disabled (default)
define('CUSTOM_CACHE_TIMEOUT', false);

date_default_timezone_set('UTC');
error_reporting(0);

// Specify directory for cached files (using FileCache)
define('CACHE_DIR', __DIR__ . '/cache');

// Specify path for whitelist file
define('WHITELIST_FILE', __DIR__ . '/whitelist.txt');


/*
Move the CLI arguments to the $_GET array, in order to be able to use
rss-bridge from the command line
*/
parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
$params = array_merge($_GET, $cliArgs);

/*
  Create a file named 'DEBUG' for enabling debug mode.
  For further security, you may put whitelisted IP addresses in the file,
  one IP per line. Empty file allows anyone(!).
  Debugging allows displaying PHP error messages and bypasses the cache: this
  can allow a malicious client to retrieve data about your server and hammer
  a provider throught your rss-bridge instance.
*/
if(file_exists('DEBUG')) {
	$debug_whitelist = trim(file_get_contents('DEBUG'));

	$debug_enabled = empty($debug_whitelist)
	|| in_array($_SERVER['REMOTE_ADDR'], explode("\n", $debug_whitelist));

	if($debug_enabled) {
		ini_set('display_errors', '1');
		error_reporting(E_ALL);
		define('DEBUG', true);
	}
}

require_once __DIR__ . '/lib/RssBridge.php';

// Check PHP version
if(version_compare(PHP_VERSION, PHP_VERSION_REQUIRED) === -1)
	die('RSS-Bridge requires at least PHP version ' . PHP_VERSION_REQUIRED . '!');

// extensions check
if(!extension_loaded('openssl'))
	die('"openssl" extension not loaded. Please check "php.ini"');

if(!extension_loaded('libxml'))
	die('"libxml" extension not loaded. Please check "php.ini"');

if(!extension_loaded('mbstring'))
	die('"mbstring" extension not loaded. Please check "php.ini"');

if(!extension_loaded('simplexml'))
	die('"simplexml" extension not loaded. Please check "php.ini"');

if(!extension_loaded('curl'))
	die('"curl" extension not loaded. Please check "php.ini"');

// configuration checks
if(ini_get('allow_url_fopen') !== "1")
	die('"allow_url_fopen" is not set to "1". Please check "php.ini');

// Check cache folder permissions (write permissions required)
if(!is_writable(CACHE_DIR))
	die('RSS-Bridge does not have write permissions for ' . CACHE_DIR . '!');

// Check whitelist file permissions (only in DEBUG mode)
if(!file_exists(WHITELIST_FILE) && !is_writable(dirname(WHITELIST_FILE)))
	die('RSS-Bridge does not have write permissions for ' . WHITELIST_FILE . '!');

// FIXME : beta test UA spoofing, please report any blacklisting by PHP-fopen-unfriendly websites

$userAgent = 'Mozilla/5.0(X11; Linux x86_64; rv:30.0)';
$userAgent .= ' Gecko/20121202 Firefox/30.0(rss-bridge/0.1;';
$userAgent .= '+https://github.com/RSS-Bridge/rss-bridge)';

ini_set('user_agent', $userAgent);

// default whitelist
$whitelist_default = array(
	'BandcampBridge',
	'CryptomeBridge',
	'DansTonChatBridge',
	'DuckDuckGoBridge',
	'FacebookBridge',
	'FlickrExploreBridge',
	'GooglePlusPostBridge',
	'GoogleSearchBridge',
	'IdenticaBridge',
	'InstagramBridge',
	'OpenClassroomsBridge',
	'PinterestBridge',
	'ScmbBridge',
	'TwitterBridge',
	'WikipediaBridge',
	'YoutubeBridge');

try {

	Bridge::setDir(__DIR__ . '/bridges/');
	Format::setDir(__DIR__ . '/formats/');
	Cache::setDir(__DIR__ . '/caches/');

	if(!file_exists(WHITELIST_FILE)) {
		$whitelist_selection = $whitelist_default;
		$whitelist_write = implode("\n", $whitelist_default);
		file_put_contents(WHITELIST_FILE, $whitelist_write);
	} else {

		$whitelist_file_content = file_get_contents(WHITELIST_FILE);
		if($whitelist_file_content != "*\n") {
			$whitelist_selection = explode("\n", $whitelist_file_content);
		} else {
			$whitelist_selection = Bridge::listBridges();
		}

		// Prepare for case-insensitive match
		$whitelist_selection = array_map('strtolower', $whitelist_selection);
	}

	$action = array_key_exists('action', $params) ? $params['action'] : null;
	$bridge = array_key_exists('bridge', $params) ? $params['bridge'] : null;

	if($action === 'display' && !empty($bridge)) {
		// DEPRECATED: 'nameBridge' scheme is replaced by 'name' in bridge parameter values
		//             this is to keep compatibility until futher complete removal
		if(($pos = strpos($bridge, 'Bridge')) === (strlen($bridge) - strlen('Bridge'))) {
			$bridge = substr($bridge, 0, $pos);
		}

		$format = $params['format']
			or returnClientError('You must specify a format!');

		// DEPRECATED: 'nameFormat' scheme is replaced by 'name' in format parameter values
		//             this is to keep compatibility until futher complete removal
		if(($pos = strpos($format, 'Format')) === (strlen($format) - strlen('Format'))) {
			$format = substr($format, 0, $pos);
		}

		// whitelist control
		if(!Bridge::isWhitelisted($whitelist_selection, strtolower($bridge))) {
			throw new \HttpException('This bridge is not whitelisted', 401);
			die;
		}

		// Data retrieval
		$bridge = Bridge::create($bridge);

		$noproxy = array_key_exists('_noproxy', $params) && filter_var($params['_noproxy'], FILTER_VALIDATE_BOOLEAN);
		if(defined('PROXY_URL') && PROXY_BYBRIDGE && $noproxy) {
			define('NOPROXY', true);
		}

		// Custom cache timeout
		$cache_timeout = -1;
		if(array_key_exists('_cache_timeout', $params)) {
			if(!CUSTOM_CACHE_TIMEOUT) {
				throw new \HttpException('This server doesn\'t support "_cache_timeout"!');
			}

			$cache_timeout = filter_var($params['_cache_timeout'], FILTER_VALIDATE_INT);
		}

		// Initialize cache
		$cache = Cache::create('FileCache');
		$cache->setPath(CACHE_DIR);
		$cache->purgeCache(86400); // 24 hours
		$cache->setParameters($params);

		unset($params['action']);
		unset($params['bridge']);
		unset($params['format']);
		unset($params['_noproxy']);
		unset($params['_cache_timeout']);

		// Load cache & data
		try {
			$bridge->setCache($cache);
			$bridge->setCacheTimeout($cache_timeout);
			$bridge->setDatas($params);
		} catch(Error $e) {
			http_response_code($e->getCode());
			header('Content-Type: text/html');
			die(buildBridgeException($e, $bridge));
		} catch(Exception $e) {
			http_response_code($e->getCode());
			header('Content-Type: text/html');
			die(buildBridgeException($e, $bridge));
		}

		// Data transformation
		try {
			$format = Format::create($format);
			$format->setItems($bridge->getItems());
			$format->setExtraInfos($bridge->getExtraInfos());
			$format->display();
		} catch(Error $e) {
			http_response_code($e->getCode());
			header('Content-Type: text/html');
			die(buildTransformException($e, $bridge));
		} catch(Exception $e) {
			http_response_code($e->getCode());
			header('Content-Type: text/html');
			die(buildBridgeException($e, $bridge));
		}

		die;
	}
} catch(HttpException $e) {
	http_response_code($e->getCode());
	header('Content-Type: text/plain');
	die($e->getMessage());
} catch(\Exception $e) {
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
	<link href="static/style.css" rel="stylesheet">
	<script src="static/search.js"></script>
	<script src="static/select.js"></script>
	<noscript>
		<style>
			.searchbar {
				display: none;
			}
		</style>
	</noscript>
</head>

<body onload="search()">
	<?php
		$status = '';
		if(defined('DEBUG') && DEBUG === true) {
			$status .= 'debug mode active';
		}

		$query = filter_input(INPUT_GET, 'q');

		echo <<<EOD
	<header>
		<h1>RSS-Bridge</h1>
		<h2>·Reconnecting the Web·</h2>
		<p class="status">{$status}</p>
	</header>
	<section class="searchbar">
		<h3>Search</h3>
		<input type="text" name="searchfield"
			id="searchfield" placeholder="Enter the bridge you want to search for"
			onchange="search()" onkeyup="search()" value="{$query}">
	</section>

EOD;

		$activeFoundBridgeCount = 0;
		$showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
		$inactiveBridges = '';
		$bridgeList = Bridge::listBridges();
		foreach($bridgeList as $bridgeName) {
			if(Bridge::isWhitelisted($whitelist_selection, strtolower($bridgeName))) {
				echo displayBridgeCard($bridgeName, $formats);
						$activeFoundBridgeCount++;
			} elseif($showInactive) {
				// inactive bridges
				$inactiveBridges .= displayBridgeCard($bridgeName, $formats, false) . PHP_EOL;
			}
		}
		echo $inactiveBridges;
	?>
	<section class="footer">
		<a href="https://github.com/RSS-Bridge/rss-bridge">RSS-Bridge 2018-04-20 ~ Public Domain</a><br />
		<?= $activeFoundBridgeCount; ?>/<?= count($bridgeList) ?> active bridges. <br />
		<?php
			if($activeFoundBridgeCount !== count($bridgeList)) {
				// FIXME: This should be done in pure CSS
				if(!$showInactive)
					echo '<a href="?show_inactive=1"><button class="small">Show inactive bridges</button></a><br />';
				else
					echo '<a href="?show_inactive=0"><button class="small">Hide inactive bridges</button></a><br />';
			}
		?>
	</section>
	</body>
</html>
