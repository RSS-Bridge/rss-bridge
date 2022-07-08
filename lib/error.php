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

/**
 * Throws an exception when called.
 *
 * @throws \Exception when called
 * @param string $message The error message
 * @param int $code The HTTP error code
 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes List of HTTP
 * status codes
 */
function returnError($message, $code)
{
    throw new \Exception($message, $code);
}

/**
 * Returns HTTP Error 400 (Bad Request) when called.
 *
 * @param string $message The error message
 */
function returnClientError($message)
{
    returnError($message, 400);
}

/**
 * Returns HTTP Error 500 (Internal Server Error) when called.
 *
 * @param string $message The error message
 */
function returnServerError($message)
{
    returnError($message, 500);
}

/**
 * Stores bridge-specific errors in a cache file.
 *
 * @param string $bridgeName The name of the bridge that failed.
 * @param int $code The error code
 *
 * @return int The total number the same error has appeared
 */
function logBridgeError($bridgeName, $code)
{
    $cacheFactory = new CacheFactory();

    $cache = $cacheFactory->create();
    $cache->setScope('error_reporting');
    $cache->setkey($bridgeName . '_' . $code);
    $cache->purgeCache(86400); // 24 hours

    if ($report = $cache->loadData()) {
        $report = json_decode($report, true);
        $report['time'] = time();
        $report['count']++;
    } else {
        $report = [
            'error' => $code,
            'time' => time(),
            'count' => 1,
        ];
    }

    $cache->saveData(json_encode($report));

    return $report['count'];
}

function create_sane_stacktrace(\Throwable $e): array
{
    $frames = array_reverse($e->getTrace());
    $frames[] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
    $stackTrace = [];
    foreach ($frames as $i => $frame) {
        $file = $frame['file'] ?? '(no file)';
        $line = $frame['line'] ?? '(no line)';
        $stackTrace[] = sprintf(
            '#%s %s:%s',
            $i,
            trim_path_prefix($file),
            $line,
        );
    }
    return $stackTrace;
}

/**
 * Trim path prefix for privacy/security reasons
 *
 * Example: "/var/www/rss-bridge/index.php" => "index.php"
 */
function trim_path_prefix(string $filePath): string
{
    return mb_substr($filePath, mb_strlen(dirname(__DIR__)) + 1);
}
