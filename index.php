<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    http_response_code(500);
    print 'RSS-Bridge requires minimum PHP version 7.4';
    exit;
}

if (! is_readable(__DIR__ . '/lib/bootstrap.php')) {
    http_response_code(500);
    print 'Unable to read lib/bootstrap.php. Check file permissions.';
    exit;
}

require_once __DIR__ . '/lib/bootstrap.php';

set_exception_handler(function (\Throwable $e) {
    $response = new Response(render(__DIR__ . '/templates/exception.html.php', ['e' => $e]), 500);
    $response->send();
    RssBridge::getLogger()->error('Uncaught Exception', ['e' => $e]);
});

set_error_handler(function ($code, $message, $file, $line) {
    if ((error_reporting() & $code) === 0) {
        // Deprecation messages and other masked errors are typically ignored here
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
            // This output can interfere with json output etc
            // This output is written at the bottom
            print sprintf("<pre>%s</pre>\n", e($message));
        }
    }
});

$errors = Configuration::checkInstallation();
if ($errors) {
    http_response_code(500);
    print '<pre>' . implode("\n", $errors) . '</pre>';
    exit;
}

// Consider: ini_set('error_reporting', E_ALL & ~E_DEPRECATED);

date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

try {
    $rssBridge = new RssBridge();
    $response = $rssBridge->main($argv ?? []);
    $response->send();
} catch (\Throwable $e) {
    // Probably an exception inside an action
    RssBridge::getLogger()->error('Exception in RssBridge::main()', ['e' => $e]);
    http_response_code(500);
    print render(__DIR__ . '/templates/exception.html.php', ['e' => $e]);
}
