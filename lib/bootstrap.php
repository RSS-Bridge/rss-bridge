<?php

// Path to the formats library
const PATH_LIB_FORMATS = __DIR__ . '/../formats/';

/** Path to the caches library */
const PATH_LIB_CACHES = __DIR__ . '/../caches/';

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
    __DIR__ . '/../lib/logger.php',
    __DIR__ . '/../lib/url.php',
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
