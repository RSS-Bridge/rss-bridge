<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    exit('RSS-Bridge requires minimum PHP version 7.4.0!');
}

require_once __DIR__ . '/lib/bootstrap.php';

$errors = Configuration::checkInstallation();
if ($errors) {
    die('<pre>' . implode("\n", $errors) . '</pre>');
}

$customConfig = [];
if (file_exists(__DIR__ . '/config.ini.php')) {
    $customConfig = parse_ini_file(__DIR__ . '/config.ini.php', true, INI_SCANNER_TYPED);
}
Configuration::loadConfiguration($customConfig, getenv());

// Consider: ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
date_default_timezone_set(Configuration::getConfig('system', 'timezone'));

$rssBridge = new RssBridge();

set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    print render(__DIR__ . '/templates/exception.html.php', ['e' => $e]);
    RssBridge::getLogger()->error('Uncaught Exception', ['e' => $e]);
    exit(1);
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

$rssBridge->main($argv ?? []);
