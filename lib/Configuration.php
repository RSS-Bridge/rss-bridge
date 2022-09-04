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
 * Configuration module for RSS-Bridge.
 *
 * This class implements a configuration module for RSS-Bridge.
 */
final class Configuration
{
    private const VERSION = 'dev.2022-06-14';

    private static $config = [];

    private function __construct()
    {
    }

    /**
     * Verifies the current installation of RSS-Bridge and PHP.
     *
     * Returns an error message and aborts execution if the installation does
     * not satisfy the requirements of RSS-Bridge.
     *
     * @return void
     */
    public static function verifyInstallation()
    {
        if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
            throw new \Exception('RSS-Bridge requires at least PHP version 7.4.0!');
        }

        $errors = [];

        // OpenSSL: https://www.php.net/manual/en/book.openssl.php
        if (!extension_loaded('openssl')) {
            $errors[] = 'openssl extension not loaded';
        }

        // libxml: https://www.php.net/manual/en/book.libxml.php
        if (!extension_loaded('libxml')) {
            $errors[] = 'libxml extension not loaded';
        }

        // Multibyte String (mbstring): https://www.php.net/manual/en/book.mbstring.php
        if (!extension_loaded('mbstring')) {
            $errors[] = 'mbstring extension not loaded';
        }

        // SimpleXML: https://www.php.net/manual/en/book.simplexml.php
        if (!extension_loaded('simplexml')) {
            $errors[] = 'simplexml extension not loaded';
        }

        // Client URL Library (curl): https://www.php.net/manual/en/book.curl.php
        // Allow RSS-Bridge to run without curl module in CLI mode without root certificates
        if (!extension_loaded('curl') && !(php_sapi_name() === 'cli' && empty(ini_get('curl.cainfo')))) {
            $errors[] = 'curl extension not loaded';
        }

        // JavaScript Object Notation (json): https://www.php.net/manual/en/book.json.php
        if (!extension_loaded('json')) {
            $errors[] = 'json extension not loaded';
        }

        if ($errors) {
            throw new \Exception(sprintf('Configuration error: %s', implode(', ', $errors)));
        }
    }

    public static function loadConfiguration(array $customConfig = [], array $env = [])
    {
        if (!file_exists(__DIR__ . '/../config.default.ini.php')) {
            throw new \Exception('The default configuration file is missing');
        }
        $config = parse_ini_file(__DIR__ . '/../config.default.ini.php', true, INI_SCANNER_TYPED);
        if (!$config) {
            throw new \Exception('Error parsing config');
        }
        foreach ($config as $header => $section) {
            foreach ($section as $key => $value) {
                self::setConfig($header, $key, $value);
            }
        }
        foreach ($customConfig as $header => $section) {
            foreach ($section as $key => $value) {
                self::setConfig($header, $key, $value);
            }
        }
        foreach ($env as $envName => $envValue) {
            $nameParts = explode('_', $envName);
            if ($nameParts[0] === 'RSSBRIDGE') {
                $header = $nameParts[1];
                $key = $nameParts[2];
                if ($envValue === 'true' || $envValue === 'false') {
                    $envValue = filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
                }
                self::setConfig($header, $key, $envValue);
            }
        }

        if (
            !is_string(self::getConfig('system', 'timezone'))
            || !in_array(self::getConfig('system', 'timezone'), timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))
        ) {
            self::throwConfigError('system', 'timezone');
        }

        if (!is_string(self::getConfig('proxy', 'url'))) {
            self::throwConfigError('proxy', 'url', 'Is not a valid string');
        }

        if (!is_bool(self::getConfig('proxy', 'by_bridge'))) {
            self::throwConfigError('proxy', 'by_bridge', 'Is not a valid Boolean');
        }

        if (!is_string(self::getConfig('proxy', 'name'))) {
            /** Name of the proxy server */
            self::throwConfigError('proxy', 'name', 'Is not a valid string');
        }

        if (!is_string(self::getConfig('cache', 'type'))) {
            self::throwConfigError('cache', 'type', 'Is not a valid string');
        }

        if (!is_bool(self::getConfig('cache', 'custom_timeout'))) {
            self::throwConfigError('cache', 'custom_timeout', 'Is not a valid Boolean');
        }

        if (!is_bool(self::getConfig('authentication', 'enable'))) {
            self::throwConfigError('authentication', 'enable', 'Is not a valid Boolean');
        }

        if (!is_string(self::getConfig('authentication', 'username'))) {
            self::throwConfigError('authentication', 'username', 'Is not a valid string');
        }

        if (!is_string(self::getConfig('authentication', 'password'))) {
            self::throwConfigError('authentication', 'password', 'Is not a valid string');
        }

        if (
            !empty(self::getConfig('admin', 'email'))
            && !filter_var(self::getConfig('admin', 'email'), FILTER_VALIDATE_EMAIL)
        ) {
            self::throwConfigError('admin', 'email', 'Is not a valid email address');
        }

        if (!is_bool(self::getConfig('admin', 'donations'))) {
            self::throwConfigError('admin', 'donations', 'Is not a valid Boolean');
        }

        if (!is_string(self::getConfig('error', 'output'))) {
            self::throwConfigError('error', 'output', 'Is not a valid String');
        }

        if (
            !is_numeric(self::getConfig('error', 'report_limit'))
            || self::getConfig('error', 'report_limit') < 1
        ) {
            self::throwConfigError('admin', 'report_limit', 'Value is invalid');
        }
    }

    public static function getConfig(string $section, string $key)
    {
        return self::$config[strtolower($section)][strtolower($key)] ?? null;
    }

    private static function setConfig(string $section, string $key, $value): void
    {
        self::$config[strtolower($section)][strtolower($key)] = $value;
    }

    public static function getVersion()
    {
        $headFile = __DIR__ . '/../.git/HEAD';

        if (@is_readable($headFile)) {
            $revisionHashFile = '.git/' . substr(file_get_contents($headFile), 5, -1);
            $parts = explode('/', $revisionHashFile);

            if (isset($parts[3])) {
                $branchName = $parts[3];
                if (file_exists($revisionHashFile)) {
                    return sprintf('%s (git.%s.%s)', self::VERSION, $branchName, substr(file_get_contents($revisionHashFile), 0, 7));
                }
            }
        }
        return self::VERSION;
    }

    private static function throwConfigError($section, $key, $message = '')
    {
        throw new \Exception("Config [$section] => [$key] is invalid. $message");
    }
}
