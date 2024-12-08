<?php

/**
 * Configuration module for RSS-Bridge.
 *
 * This class implements a configuration module for RSS-Bridge.
 */
final class Configuration
{
    private const VERSION = '2024-02-02';

    private static $config = [];

    private function __construct()
    {
    }

    public static function loadConfiguration(array $customConfig = [], array $env = [])
    {
        if (!file_exists(__DIR__ . '/../config.default.ini.php')) {
            throw new \Exception('The default configuration file is missing');
        }
        $config = parse_ini_file(__DIR__ . '/../config.default.ini.php', true, INI_SCANNER_TYPED);
        if (!$config) {
            throw new \Exception('Error parsing ini config');
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

        if (file_exists(__DIR__ . '/../DEBUG')) {
            // The debug mode has been moved to config. Preserve existing installs which has this DEBUG file.
            self::setConfig('system', 'enable_debug_mode', true);
            $debug = trim(file_get_contents(__DIR__ . '/../DEBUG'));
            if ($debug) {
                self::setConfig('system', 'debug_mode_whitelist', explode("\n", str_replace("\r", '', $debug)));
            }
        }

        if (file_exists(__DIR__ . '/../whitelist.txt')) {
            $enabledBridges = trim(file_get_contents(__DIR__ . '/../whitelist.txt'));
            if ($enabledBridges === '*') {
                self::setConfig('system', 'enabled_bridges', ['*']);
            } else {
                self::setConfig('system', 'enabled_bridges', array_filter(array_map('trim', explode("\n", $enabledBridges))));
            }
        }

        foreach ($env as $envName => $envValue) {
            $nameParts = explode('_', $envName);
            if ($nameParts[0] === 'RSSBRIDGE') {
                if (count($nameParts) < 3) {
                    // Invalid env name
                    continue;
                }

                // The variable is named $header but it's actually the section in config.ini.php
                $header = $nameParts[1];

                // Recombine the key if it had multiple underscores
                $key = implode('_', array_slice($nameParts, 2));
                $key = strtolower($key);

                // Handle this specifically because it's an array
                if ($key === 'enabled_bridges') {
                    $envValue = explode(',', $envValue);
                    $envValue = array_map('trim', $envValue);
                }

                if ($envValue === 'true' || $envValue === 'false') {
                    $envValue = filter_var($envValue, FILTER_VALIDATE_BOOLEAN);
                }

                self::setConfig($header, $key, $envValue);
            }
        }

        if (Debug::isEnabled()) {
            self::setConfig('cache', 'type', 'array');
        }

        if (!is_array(self::getConfig('system', 'enabled_bridges'))) {
            self::throwConfigError('system', 'enabled_bridges', 'Is not an array');
        }

        if (
            !is_string(self::getConfig('system', 'timezone'))
            || !in_array(self::getConfig('system', 'timezone'), timezone_identifiers_list(DateTimeZone::ALL_WITH_BC))
        ) {
            self::throwConfigError('system', 'timezone');
        }

        if (!is_bool(self::getConfig('system', 'enable_debug_mode'))) {
            self::throwConfigError('system', 'enable_debug_mode', 'Is not a valid Boolean');
        }
        if (!is_array(self::getConfig('system', 'debug_mode_whitelist') ?: [])) {
            self::throwConfigError('system', 'debug_mode_whitelist', 'Is not a valid array');
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
        if (!in_array(self::getConfig('error', 'output'), ['feed', 'http', 'none'])) {
            self::throwConfigError('error', 'output', 'Invalid output');
        }

        if (
            !is_numeric(self::getConfig('error', 'report_limit'))
            || self::getConfig('error', 'report_limit') < 1
        ) {
            self::throwConfigError('admin', 'report_limit', 'Value is invalid');
        }
    }

    public static function getConfig(string $section, string $key, $default = null)
    {
        if (self::$config === []) {
            throw new \Exception('Config has not been loaded');
        }
        return self::$config[strtolower($section)][strtolower($key)] ?? $default;
    }

    /**
     * @internal Please avoid usage
     */
    public static function setConfig(string $section, string $key, $value): void
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
