<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    http_response_code(500);
    exit("RSS-Bridge requires minimum PHP version 7.4\n");
}

require_once __DIR__ . '/lib/bootstrap.php';

$config = [];
if (file_exists(__DIR__ . '/config.ini.php')) {
    $config = parse_ini_file(__DIR__ . '/config.ini.php', true, INI_SCANNER_TYPED);
    if (!$config) {
        http_response_code(500);
        exit("Error parsing config.ini.php\n");
    }
}
Configuration::loadConfiguration($config, getenv());

$logger = new SimpleLogger('rssbridge');

set_exception_handler(function (\Throwable $e) use ($logger) {
    $response = new Response(render(__DIR__ . '/templates/exception.html.php', ['e' => $e]), 500);
    $response->send();
    $logger->error('Uncaught Exception', ['e' => $e]);
});

set_error_handler(function ($code, $message, $file, $line) use ($logger) {
    // Consider: ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
    if ((error_reporting() & $code) === 0) {
        // Deprecation messages and other masked errors are typically ignored here
        return false;
    }
    if (Debug::isEnabled()) {
        // This might be annoying, but it's for the greater good
        throw new \ErrorException($message, 0, $code, $file, $line);
    }
    $text = sprintf(
        '%s at %s line %s',
        sanitize_root($message),
        sanitize_root($file),
        $line
    );
    $logger->warning($text);
    // todo: return false to prevent default error handler from running?
});

// There might be some fatal errors which are not caught by set_error_handler() or \Throwable.
register_shutdown_function(function () use ($logger) {
    $error = error_get_last();
    if ($error) {
        $message = sprintf(
            '(shutdown) %s: %s in %s line %s',
            $error['type'],
            sanitize_root($error['message']),
            sanitize_root($error['file']),
            $error['line']
        );
        $logger->error($message);
    }
});

$cacheFactory = new CacheFactory($logger);

// Uncomment this for debug logging
// $logger->addHandler(new StreamHandler('/tmp/rss-bridge.txt', Logger::DEBUG));

if (Debug::isEnabled()) {
    $logger->addHandler(new ErrorLogHandler(Logger::DEBUG));
    $cache = $cacheFactory->create('array');
} else {
    $logger->addHandler(new ErrorLogHandler(Logger::INFO));
    $cache = $cacheFactory->create();
}
$httpClient = new CurlHttpClient();

date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

try {
    $rssBridge = new RssBridge($logger, $cache, $httpClient);
    $response = $rssBridge->main($argv ?? []);
    $response->send();
} catch (\Throwable $e) {
    // Probably an exception inside an action
    $logger->error('Exception in RssBridge::main()', ['e' => $e]);
    $response = new Response(render(__DIR__ . '/templates/exception.html.php', ['e' => $e]), 500);
    $response->send();
}
