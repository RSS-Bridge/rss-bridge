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

    private static $config = null;

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
            self::reportError('RSS-Bridge requires at least PHP version 7.4.0!');
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

    /**
     * Loads the configuration from disk and checks if the parameters are valid.
     *
     * Returns an error message and aborts execution if the configuration is invalid.
     *
     * The RSS-Bridge configuration is split into two files:
     * - The default configuration file that ships
     * with every release of RSS-Bridge (do not modify this file!).
     * - The local configuration file that can be modified
     * by server administrators.
     *
     * RSS-Bridge will first load default config into memory and then
     * replace parameters with the contents of the custom config. That way new
     * parameters are automatically initialized with default values and custom
     * configurations can be reduced to the minimum set of parametes necessary
     * (only the ones that changed).
     *
     * The configuration files must be placed in the root folder of RSS-Bridge
     * (next to `index.php`).
     *
     * _Notice_: The configuration is stored in {@see Configuration::$config}.
     *
     * @return void
     */
    public static function loadConfiguration()
    {
        $env = getenv();
        $defaultConfig = __DIR__ . '/../config.default.ini.php';
        $customConfig = __DIR__ . '/../config.ini.php';

        if (!file_exists($defaultConfig)) {
            self::reportError('The default configuration file is missing');
        }

        $config = parse_ini_file($defaultConfig, true, INI_SCANNER_TYPED);
        if (!$config) {
            self::reportError('Error parsing config');
        }

        if (file_exists($customConfig)) {
            // Replace default configuration with custom settings
            foreach (parse_ini_file($customConfig, true, INI_SCANNER_TYPED) as $header => $section) {
                foreach ($section as $key => $value) {
                    $config[$header][$key] = $value;
                }
            }
        }

        foreach ($env as $envName => $envValue) {
            // Replace all settings with their respective environment variable if available
            $keyArray = explode('_', $envName);
            if ($keyArray[0] === 'RSSBRIDGE') {
                $header = strtolower($keyArray[1]);
                $key = strtolower($keyArray[2]);
                if ($envValue === 'true' || $envValue === 'false') {
                    $envValue = filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
                }
                $config[$header][$key] = $envValue;
            }
        }

        self::$config = $config;

        if (
            !is_string(self::getConfig('system', 'timezone'))
            || !in_array(self::getConfig('system', 'timezone'), timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))
        ) {
            self::reportConfigurationError('system', 'timezone');
        }

        if (!is_string(self::getConfig('proxy', 'url'))) {
            self::reportConfigurationError('proxy', 'url', 'Is not a valid string');
        }

        if (!is_bool(self::getConfig('proxy', 'by_bridge'))) {
            self::reportConfigurationError('proxy', 'by_bridge', 'Is not a valid Boolean');
        }

        if (!is_string(self::getConfig('proxy', 'name'))) {
            /** Name of the proxy server */
            self::reportConfigurationError('proxy', 'name', 'Is not a valid string');
        }

        if (!is_string(self::getConfig('cache', 'type'))) {
            self::reportConfigurationError('cache', 'type', 'Is not a valid string');
        }

        if (!is_bool(self::getConfig('cache', 'custom_timeout'))) {
            self::reportConfigurationError('cache', 'custom_timeout', 'Is not a valid Boolean');
        }

        if (!is_bool(self::getConfig('authentication', 'enable'))) {
            self::reportConfigurationError('authentication', 'enable', 'Is not a valid Boolean');
        }

        if (!self::getConfig('authentication', 'username')) {
            self::reportConfigurationError('authentication', 'username', 'Is not a valid string');
        }

        if (! self::getConfig('authentication', 'password')) {
            self::reportConfigurationError('authentication', 'password', 'Is not a valid string');
        }

        if (
            !empty(self::getConfig('admin', 'email'))
            && !filter_var(self::getConfig('admin', 'email'), FILTER_VALIDATE_EMAIL)
        ) {
            self::reportConfigurationError('admin', 'email', 'Is not a valid email address');
        }

        if (!is_bool(self::getConfig('admin', 'donations'))) {
            self::reportConfigurationError('admin', 'donations', 'Is not a valid Boolean');
        }

        if (!is_string(self::getConfig('error', 'output'))) {
            self::reportConfigurationError('error', 'output', 'Is not a valid String');
        }

        if (
            !is_numeric(self::getConfig('error', 'report_limit'))
            || self::getConfig('error', 'report_limit') < 1
        ) {
            self::reportConfigurationError('admin', 'report_limit', 'Value is invalid');
        }
    }

    /**
     * Returns the value of a parameter identified by section and key.
     *
     * @param string $section The section name.
     * @param string $key The property name (key).
     * @return mixed|null The parameter value.
     */
    public static function getConfig($section, $key)
    {
        if (array_key_exists($section, self::$config) && array_key_exists($key, self::$config[$section])) {
            return self::$config[$section][$key];
        }

        return null;
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
                    return 'git.' . $branchName . '.' . substr(file_get_contents($revisionHashFile), 0, 7);
                }
            }
        }
        return self::VERSION;
    }

    /**
     * Reports an configuration error for the specified section and key to the
     * user and ends execution
     *
     * @param string $section The section name
     * @param string $key The configuration key
     * @param string $message An optional message to the user
     *
     * @return void
     */
    private static function reportConfigurationError($section, $key, $message = '')
    {
        $report = "Parameter [{$section}] => \"{$key}\" is invalid!\n$message";
        self::reportError($report);
    }

    private static function reportError($message)
    {
        throw new \Exception(sprintf('Configuration error: %s', $message));
    }
}
