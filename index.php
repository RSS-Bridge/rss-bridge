<?php

require_once __DIR__ . '/lib/bootstrap.php';

// Consider: ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

set_exception_handler(function (\Throwable $e) {
    RssBridge::getLogger()->error('Uncaught Exception', ['e' => $e]);
    http_response_code(500);
    exit(render(__DIR__ . '/templates/exception.html.php', ['e' => $e]));
});

set_error_handler(function ($code, $message, $file, $line) {
    if ((error_reporting() & $code) === 0) {
        return false;
    }
    // In the future, uncomment this:
    //throw new \ErrorException($message, 0, $code, $file, $line);
    $text = sprintf(
        '%s at %s line %s',
        sanitize_root($message),
        sanitize_root($file),
        $line
    );
    RssBridge::getLogger()->warning($text);
});

// There might be some fatal errors which are not caught by set_error_handler() or \Throwable.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        $message = sprintf(
            '(shutdown) %s: %s in %s line %s',
            $error['type'],
            sanitize_root($error['message']),
            sanitize_root($error['file']),
            $error['line']
        );
        RssBridge::getLogger()->error($message);
        if (Debug::isEnabled()) {
            print sprintf("<pre>%s</pre>\n", e($message));
        }
    }
});

$rssBridge = new RssBridge();

$rssBridge->main($argv ?? []);
