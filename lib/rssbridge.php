<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/** Path to the root folder of RSS-Bridge (where index.php is located) */
define('PATH_ROOT', __DIR__ . '/../');

/** Path to the core library */
define('PATH_LIB', __DIR__ . '/../lib/'); // Path to core library

/** Path to the vendor library */
define('PATH_LIB_VENDOR', __DIR__ . '/../vendor/');

/** Path to the bridges library */
define('PATH_LIB_BRIDGES', __DIR__ . '/../bridges/');

/** Path to the formats library */
define('PATH_LIB_FORMATS', __DIR__ . '/../formats/');

/** Path to the caches library */
define('PATH_LIB_CACHES', __DIR__ . '/../caches/');

/** Path to the actions library */
define('PATH_LIB_ACTIONS', __DIR__ . '/../actions/');

/** Path to the cache folder */
define('PATH_CACHE', __DIR__ . '/../cache/');

/** Path to the whitelist file */
define('WHITELIST', __DIR__ . '/../whitelist.txt');

/** URL to the RSS-Bridge repository */
define('REPOSITORY', 'https://github.com/RSS-Bridge/rss-bridge/');

// Interfaces
require_once PATH_LIB . 'ActionInterface.php';
require_once PATH_LIB . 'BridgeInterface.php';
require_once PATH_LIB . 'CacheInterface.php';
require_once PATH_LIB . 'FormatInterface.php';

// Classes
require_once PATH_LIB . 'FactoryAbstract.php';
require_once PATH_LIB . 'FeedItem.php';
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
require_once PATH_LIB . 'ActionFactory.php';
require_once PATH_LIB . 'ActionAbstract.php';

// Functions
require_once PATH_LIB . 'html.php';
require_once PATH_LIB . 'error.php';
require_once PATH_LIB . 'contents.php';

// Vendor
define('MAX_FILE_SIZE', 10000000); /* Allow larger files for simple_html_dom */
require_once PATH_LIB_VENDOR . 'simplehtmldom/simple_html_dom.php';
require_once PATH_LIB_VENDOR . 'php-urljoin/src/urljoin.php';

// Initialize static members
try {
	Bridge::setWorkingDir(PATH_LIB_BRIDGES);
	Format::setWorkingDir(PATH_LIB_FORMATS);
	Cache::setWorkingDir(PATH_LIB_CACHES);
} catch(Exception $e) {
	error_log($e);
	header('Content-type: text/plain', true, 500);
	die($e->getMessage());
}
