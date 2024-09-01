<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    http_response_code(500);
    exit("RSS-Bridge requires minimum PHP version 7.4\n");
}

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/config.php';

$container = require __DIR__ . '/lib/dependencies.php';

$logger = $container['logger'];

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

date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

$argv = $argv ?? null;
if ($argv) {
    parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
    $request = Request::fromCli($cliArgs);
} else {
    $request = Request::fromGlobals();
}

$rssBridge = new RssBridge($container);

$response = $rssBridge->main($request);

$response->send();