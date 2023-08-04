<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/** Path to the root folder of RSS-Bridge (where index.php is located) */
const PATH_ROOT = __DIR__ . '/../';

/** Path to the bridges library */

/** Path to the formats library */
const PATH_LIB_FORMATS = __DIR__ . '/../formats/';

/** Path to the caches library */
const PATH_LIB_CACHES = __DIR__ . '/../caches/';

/** Path to the actions library */
const PATH_LIB_ACTIONS = __DIR__ . '/../actions/';

/** Path to the cache folder */
const PATH_CACHE = __DIR__ . '/../cache/';

/** URL to the RSS-Bridge repository */
const REPOSITORY = 'https://github.com/RSS-Bridge/rss-bridge/';

// Allow larger files for simple_html_dom
// todo: extract to config (if possible)
const MAX_FILE_SIZE = 10000000;

// Files
$files = [
    __DIR__ . '/../lib/html.php',
    __DIR__ . '/../lib/contents.php',
    __DIR__ . '/../lib/php8backports.php',
    __DIR__ . '/../lib/utils.php',
    __DIR__ . '/../lib/http.php',
    // Vendor
    __DIR__ . '/../vendor/parsedown/Parsedown.php',
    __DIR__ . '/../vendor/php-urljoin/src/urljoin.php',
    __DIR__ . '/../vendor/simplehtmldom/simple_html_dom.php',
];
foreach ($files as $file) {
    require_once $file;
}

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
