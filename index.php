<?php
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
		|| in_array($_SERVER['REMOTE_ADDR'],
			explode("\n", str_replace("\r", '', $debug_whitelist)
		)
	);

	if($debug_enabled) {
		ini_set('display_errors', '1');
		error_reporting(E_ALL);
		define('DEBUG', true);
		if (empty($debug_whitelist)) {
			define('DEBUG_INSECURE', true);
		}
	}
}

require_once __DIR__ . '/lib/RssBridge.php';

define('PHP_VERSION_REQUIRED', '5.6.0');

// Specify directory for cached files (using FileCache)
define('CACHE_DIR', __DIR__ . '/cache');

// Specify path for whitelist file
define('WHITELIST_FILE', __DIR__ . '/whitelist.txt');

Configuration::verifyInstallation();
Configuration::loadConfiguration();

Authentication::showPromptIfNeeded();

date_default_timezone_set('UTC');

/*
Move the CLI arguments to the $_GET array, in order to be able to use
rss-bridge from the command line
*/
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
	$params = array_merge($_GET, $cliArgs);
} else {
	$params = $_GET;
}

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

	$showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
	$action = array_key_exists('action', $params) ? $params['action'] : null;
	$bridge = array_key_exists('bridge', $params) ? $params['bridge'] : null;

	// Return list of bridges as JSON formatted text
	if($action === 'list') {

		$list = new StdClass();
		$list->bridges = array();
		$list->total = 0;

		foreach(Bridge::listBridges() as $bridgeName) {

			$bridge = Bridge::create($bridgeName);

			if($bridge === false) { // Broken bridge, show as inactive

				$list->bridges[$bridgeName] = array(
					'status' => 'inactive'
				);

				continue;

			}

			$status = Bridge::isWhitelisted($whitelist_selection, strtolower($bridgeName)) ? 'active' : 'inactive';

			$list->bridges[$bridgeName] = array(
				'status' => $status,
				'uri' => $bridge->getURI(),
				'name' => $bridge->getName(),
				'icon' => $bridge->getIcon(),
				'parameters' => $bridge->getParameters(),
				'maintainer' => $bridge->getMaintainer(),
				'description' => $bridge->getDescription()
			);

		}

		$list->total = count($list->bridges);

		header('Content-Type: application/json');
		echo json_encode($list, JSON_PRETTY_PRINT);

	} elseif($action === 'display' && !empty($bridge)) {
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

		// Remove parameters that don't concern bridges
		$bridge_params = array_diff_key(
			$params,
			array_fill_keys(
				array(
					'action',
					'bridge',
					'format',
					'_noproxy',
					'_cache_timeout',
				), '')
		);

		// Remove parameters that don't concern caches
		$cache_params = array_diff_key(
			$params,
			array_fill_keys(
				array(
					'action',
					'format',
					'_noproxy',
					'_cache_timeout',
				), '')
		);

		// Initialize cache
		$cache = Cache::create('FileCache');
		$cache->setPath(CACHE_DIR);
		$cache->purgeCache(86400); // 24 hours
		$cache->setParameters($cache_params);

		// Load cache & data
		try {
			$bridge->setCache($cache);
			$bridge->setCacheTimeout($cache_timeout);
			$bridge->dieIfNotModified();
			$bridge->setDatas($bridge_params);
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
			$format->setLastModified($bridge->getCacheTime());
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
	} else {
		echo BridgeList::create($whitelist_selection, $showInactive);
	}
} catch(HttpException $e) {
	http_response_code($e->getCode());
	header('Content-Type: text/plain');
	die($e->getMessage());
} catch(\Exception $e) {
	die($e->getMessage());
}
