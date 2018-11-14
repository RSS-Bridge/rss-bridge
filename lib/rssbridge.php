<?php

define('PATH_ROOT', __DIR__ . '/../'); // Path to root folder
define('PATH_LIB', __DIR__ . '/../lib/'); // Path to core library
define('PATH_LIB_VENDOR', __DIR__ . '/../vendor/'); // Path to vendor library
define('PATH_LIB_BRIDGES', __DIR__ . '/../bridges/'); // Path to bridges library
define('PATH_LIB_FORMATS', __DIR__ . '/../formats/'); // Path to formats library
define('PATH_LIB_CACHES', __DIR__ . '/../caches/'); // Path to caches library
define('PATH_CACHE', __DIR__ . '/../cache/'); // Path to cache folder
define('WHITELIST', __DIR__ . '/../whitelist.txt'); // Path to whitelist file
define('REPOSITORY', 'https://github.com/RSS-Bridge/rss-bridge/');

// Interfaces
require_once PATH_LIB . 'BridgeInterface.php';
require_once PATH_LIB . 'CacheInterface.php';
require_once PATH_LIB . 'FormatInterface.php';

// Classes
require_once PATH_LIB . 'Debug.php';
require_once PATH_LIB . 'Exceptions.php';
require_once PATH_LIB . 'Format.php';
require_once PATH_LIB . 'FormatAbstract.php';
require_once PATH_LIB . 'Bridge.php';
require_once PATH_LIB . 'BridgeAbstract.php';
require_once PATH_LIB . 'FeedExpander.php';
require_once PATH_LIB . 'Cache.php';
require_once PATH_LIB . 'Authentication.php';
require_once PATH_LIB . 'Configuration.php';
require_once PATH_LIB . 'BridgeCard.php';
require_once PATH_LIB . 'BridgeList.php';
require_once PATH_LIB . 'ParameterValidator.php';

// Functions
require_once PATH_LIB . 'html.php';
require_once PATH_LIB . 'error.php';
require_once PATH_LIB . 'contents.php';

// Vendor
require_once PATH_LIB_VENDOR . 'simplehtmldom/simple_html_dom.php';
require_once PATH_LIB_VENDOR . 'php-urljoin/src/urljoin.php';

// Initialize static members
try {
	Bridge::setDir(PATH_LIB_BRIDGES);
	Format::setDir(PATH_LIB_FORMATS);
	Cache::setWorkingDir(PATH_LIB_CACHES);
} catch(Exception $e) {
	error_log($e);
	header('Content-type: text/plain', true, 500);
	die($e->getMessage());
}
