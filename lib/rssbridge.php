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
define('PATH_LIB', PATH_ROOT . 'lib/');

/** Path to the vendor library */
define('PATH_LIB_VENDOR', PATH_ROOT . 'vendor/');

/** Path to the bridges library */
define('PATH_LIB_BRIDGES', PATH_ROOT . 'bridges/');

/** Path to the formats library */
define('PATH_LIB_FORMATS', PATH_ROOT . 'formats/');

/** Path to the caches library */
define('PATH_LIB_CACHES', PATH_ROOT . 'caches/');

/** Path to the actions library */
define('PATH_LIB_ACTIONS', PATH_ROOT . 'actions/');

/** Path to the cache folder */
define('PATH_CACHE', PATH_ROOT . 'cache/');

/** Path to the whitelist file */
define('WHITELIST', PATH_ROOT . 'whitelist.txt');

/** Path to the default whitelist file */
define('WHITELIST_DEFAULT', PATH_ROOT . 'whitelist.default.txt');

/** Path to the configuration file */
define('FILE_CONFIG', PATH_ROOT . 'config.ini.php');

/** Path to the default configuration file */
define('FILE_CONFIG_DEFAULT', PATH_ROOT . 'config.default.ini.php');

/** URL to the RSS-Bridge repository */
define('REPOSITORY', 'https://github.com/RSS-Bridge/rss-bridge/');

// Functions
require_once PATH_LIB . 'Exceptions.php';
require_once PATH_LIB . 'html.php';
require_once PATH_LIB . 'error.php';
require_once PATH_LIB . 'contents.php';
require_once PATH_LIB . 'php8backports.php';

// Vendor
define('MAX_FILE_SIZE', 10000000); /* Allow larger files for simple_html_dom */
require_once PATH_LIB_VENDOR . 'parsedown/Parsedown.php';
require_once PATH_LIB_VENDOR . 'php-urljoin/src/urljoin.php';
require_once PATH_LIB_VENDOR . 'simplehtmldom/simple_html_dom.php';

spl_autoload_register(function ($className) {
	$folders = [
		__DIR__ . '/../actions/',
		__DIR__ . '/../bridges/',
		__DIR__ . '/../caches/',
		__DIR__ . '/../formats/',
		__DIR__ . '/../lib/',
	];
	foreach ($folders as $folder) {
		$file = $folder . $className . '.php';
		if (is_file($file)) {
			require $file;
		}
	}
});

Configuration::verifyInstallation();
Configuration::loadConfiguration();
Authentication::showPromptIfNeeded();
