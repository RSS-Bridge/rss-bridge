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
 * Implements functions for debugging purposes. Debugging can be enabled by
 * placing a file named DEBUG in {@see PATH_ROOT}.
 *
 * The file specifies a whitelist of IP addresses on which debug mode will be
 * enabled. An empty file enables debug mode for everyone (highly discouraged
 * for public servers!). Each line in the file specifies one client in the
 * whitelist. For example:
 *
 * * `192.168.1.72`
 * * `127.0.0.1`
 * * `::1`
 *
 * Notice: If you are running RSS-Bridge on your local machine, you need to add
 * localhost (either `127.0.0.1` for IPv4 or `::1` for IPv6) to your whitelist!
 *
 * Warning: In debug mode your server may display sensitive information! For
 * security reasons it is recommended to whitelist only specific IP addresses.
 */
class Debug
{
    /**
     * Indicates if debug mode is enabled.
     *
     * Do not access this property directly!
     * Use {@see Debug::isEnabled()} instead.
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * Indicates if debug mode is secure.
     *
     * Do not access this property directly!
     * Use {@see Debug::isSecure()} instead.
     *
     * @var bool
     */
    private static $secure = false;

    /**
     * Returns true if debug mode is enabled
     *
     * If debug mode is enabled, sets `display_errors = 1` and `error_reporting = E_ALL`
     *
     * @return bool True if enabled.
     */
    public static function isEnabled()
    {
        static $firstCall = true; // Initialized on first call

        if ($firstCall && file_exists(PATH_ROOT . 'DEBUG')) {
            $debug_whitelist = trim(file_get_contents(PATH_ROOT . 'DEBUG'));

            self::$enabled = empty($debug_whitelist) || in_array(
                $_SERVER['REMOTE_ADDR'],
                explode("\n", str_replace("\r", '', $debug_whitelist))
            );

            if (self::$enabled) {
                ini_set('display_errors', '1');
                error_reporting(E_ALL);

                self::$secure = !empty($debug_whitelist);
            }

            $firstCall = false; // Skip check on next call
        }

        return self::$enabled;
    }

    /**
     * Returns true if debug mode is enabled only for specific IP addresses.
     *
     * Notice: The security flag is set by {@see Debug::isEnabled()}. If this
     * function is called before {@see Debug::isEnabled()}, the default value is
     * false!
     *
     * @return bool True if debug mode is secure
     */
    public static function isSecure()
    {
        return self::$secure;
    }

    /**
     * Adds a debug message to error_log if debug mode is enabled
     *
     * @param string $text The message to add to error_log
     */
    public static function log($text)
    {
        if (!self::isEnabled()) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $calling = end($backtrace);
        $message = $calling['file'] . ':'
            . $calling['line'] . ' class '
            . (isset($calling['class']) ? $calling['class'] : '<no-class>') . '->'
            . $calling['function'] . ' - '
            . $text;

        error_log($message);
    }
}
