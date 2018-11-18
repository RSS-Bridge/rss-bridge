<?php
require_once __DIR__ . '/lib/rssbridge.php';

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

define('USER_AGENT',
	'Mozilla/5.0 (X11; Linux x86_64; rv:30.0) Gecko/20121202 Firefox/30.0(rss-bridge/'
	. Configuration::$VERSION
	. ';+'
	. REPOSITORY
	. ')'
);

ini_set('user_agent', USER_AGENT);

// default whitelist
$whitelist_default = array(
	'BandcampBridge',
	'CryptomeBridge',
	'DansTonChatBridge',
	'DuckDuckGoBridge',
	'FacebookBridge',
	'FlickrBridge',
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

	Bridge::setWhitelist($whitelist_default);

	$showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
	$action = array_key_exists('action', $params) ? $params['action'] : null;
	$bridge = array_key_exists('bridge', $params) ? $params['bridge'] : null;

	// Return list of bridges as JSON formatted text
	if($action === 'list') {

		$list = new StdClass();
		$list->bridges = array();
		$list->total = 0;

		foreach(Bridge::getBridgeNames() as $bridgeName) {

			$bridge = Bridge::create($bridgeName);

			if($bridge === false) { // Broken bridge, show as inactive

				$list->bridges[$bridgeName] = array(
					'status' => 'inactive'
				);

				continue;

			}

			$status = Bridge::isWhitelisted($bridgeName) ? 'active' : 'inactive';

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

		$format = $params['format']
			or returnClientError('You must specify a format!');

		// DEPRECATED: 'nameFormat' scheme is replaced by 'name' in format parameter values
		//             this is to keep compatibility until futher complete removal
		if(($pos = strpos($format, 'Format')) === (strlen($format) - strlen('Format'))) {
			$format = substr($format, 0, $pos);
		}

		// whitelist control
		if(!Bridge::isWhitelisted($bridge)) {
			throw new \Exception('This bridge is not whitelisted', 401);
			die;
		}

		// Data retrieval
		$bridge = Bridge::create($bridge);

		$noproxy = array_key_exists('_noproxy', $params) && filter_var($params['_noproxy'], FILTER_VALIDATE_BOOLEAN);
		if(defined('PROXY_URL') && PROXY_BYBRIDGE && $noproxy) {
			define('NOPROXY', true);
		}

		// Cache timeout
		$cache_timeout = -1;
		if(array_key_exists('_cache_timeout', $params)) {

			if(!CUSTOM_CACHE_TIMEOUT) {
				unset($params['_cache_timeout']);
				$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($params);
				header('Location: ' . $uri, true, 301);
				die();
			}

			$cache_timeout = filter_var($params['_cache_timeout'], FILTER_VALIDATE_INT);

		} else {
			$cache_timeout = $bridge->getCacheTimeout();
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
					'_error_time'
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
					'_error_time'
				), '')
		);

		// Initialize cache
		$cache = Cache::create('FileCache');
		$cache->setPath(PATH_CACHE);
		$cache->purgeCache(86400); // 24 hours
		$cache->setParameters($cache_params);

		$items = array();
		$infos = array();
		$mtime = $cache->getTime();

		if($mtime !== false
		&& (time() - $cache_timeout < $mtime)
		&& !Debug::isEnabled()) { // Load cached data

			// Send "Not Modified" response if client supports it
			// Implementation based on https://stackoverflow.com/a/10847262
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				$stime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

				if($mtime <= $stime) { // Cached data is older or same
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $mtime) . 'GMT', true, 304);
					die();
				}
			}

			$cached = $cache->loadData();

			if(isset($cached['items']) && isset($cached['extraInfos'])) {
				$items = $cached['items'];
				$infos = $cached['extraInfos'];
			}

		} else { // Collect new data

			try {
				$bridge->setDatas($bridge_params);
				$bridge->collectData();

				$items = $bridge->getItems();
				$infos = array(
					'name' => $bridge->getName(),
					'uri'  => $bridge->getURI(),
					'icon' => $bridge->getIcon()
				);
			} catch(Error $e) {
				error_log($e);

				$item = array();

				// Create "new" error message every 24 hours
				$params['_error_time'] = urlencode((int)(time() / 86400));

				// Error 0 is a special case (i.e. "trying to get property of non-object")
				if($e->getCode() === 0) {
					$item['title'] = 'Bridge encountered an unexpected situation! (' . $params['_error_time'] . ')';
				} else {
					$item['title'] = 'Bridge returned error ' . $e->getCode() . '! (' . $params['_error_time'] . ')';
				}

				$item['uri'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($params);
				$item['timestamp'] = time();
				$item['content'] = buildBridgeException($e, $bridge);

				$items[] = $item;
			} catch(Exception $e) {
				error_log($e);

				$item = array();

				// Create "new" error message every 24 hours
				$params['_error_time'] = urlencode((int)(time() / 86400));

				$item['uri'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($params);
				$item['title'] = 'Bridge returned error ' . $e->getCode() . '! (' . $params['_error_time'] . ')';
				$item['timestamp'] = time();
				$item['content'] = buildBridgeException($e, $bridge);

				$items[] = $item;
			}

			// Store data in cache
			$cache->saveData(array(
				'items' => $items,
				'extraInfos' => $infos
			));

		}

		// Data transformation
		try {
			$format = Format::create($format);
			$format->setItems($items);
			$format->setExtraInfos($infos);
			$format->setLastModified($cache->getTime());
			$format->display();
		} catch(Error $e) {
			error_log($e);
			header('Content-Type: text/html', true, $e->getCode());
			die(buildTransformException($e, $bridge));
		} catch(Exception $e) {
			error_log($e);
			header('Content-Type: text/html', true, $e->getCode());
			die(buildTransformException($e, $bridge));
		}
	} else {
		echo BridgeList::create($showInactive);
	}
} catch(\Exception $e) {
	error_log($e);
	header('Content-Type: text/plain', true, $e->getCode());
	die($e->getMessage());
}
