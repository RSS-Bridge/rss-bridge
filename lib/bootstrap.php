<?php

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

const PATH_LIB_CACHES = __DIR__ . '/../caches/';
const PATH_CACHE = __DIR__ . '/../cache/';

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
    __DIR__ . '/../lib/seotags.php',
    // Vendor
    __DIR__ . '/../lib/parsedown/Parsedown.php',
    __DIR__ . '/../lib/php-urljoin/src/urljoin.php',
    __DIR__ . '/../lib/simplehtmldom/simple_html_dom.php',
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

$customConfig = [];
if (file_exists(__DIR__ . '/../config.ini.php')) {
    $customConfig = parse_ini_file(__DIR__ . '/../config.ini.php', true, INI_SCANNER_TYPED);
}
Configuration::loadConfiguration($customConfig, getenv());
