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
 * Authentication module for RSS-Bridge.
 *
 * This class implements an authentication module for RSS-Bridge, utilizing the
 * HTTP authentication capabilities of PHP.
 *
 * _Notice_: Authentication via HTTP does not prevent users from accessing files
 * on your server. If your server supports `.htaccess`, you should globally restrict
 * access to files instead.
 *
 * @link https://php.net/manual/en/features.http-auth.php HTTP authentication with PHP
 * @link https://httpd.apache.org/docs/2.4/howto/htaccess.html Apache HTTP Server
 * Tutorial: .htaccess files
 *
 * @todo Configuration parameters should be stored internally instead of accessing
 * the configuration class directly.
 * @todo Add functions to detect if a user is authenticated or not. This can be
 * utilized for limiting access to authorized users only.
 */
class Authentication
{
    /**
     * Throw an exception when trying to create a new instance of this class.
     * Use {@see Authentication::showPromptIfNeeded()} instead!
     *
     * @throws \LogicException if called.
     */
    public function __construct()
    {
        throw new \LogicException('Use ' . __CLASS__ . '::showPromptIfNeeded()!');
    }

    /**
     * Requests the user for login credentials if necessary.
     *
     * Responds to an authentication request or returns the `WWW-Authenticate`
     * header if authentication is enabled in the configuration of RSS-Bridge
     * (`[authentication] enable = true`).
     *
     * @return void
     */
    public static function showPromptIfNeeded()
    {

        if (Configuration::getConfig('authentication', 'enable') === true) {
            if (!Authentication::verifyPrompt()) {
                header('WWW-Authenticate: Basic realm="RSS-Bridge"', true, 401);
                die('Please authenticate in order to access this instance !');
            }
        }
    }

    /**
     * Verifies if an authentication request was received and compares the
     * provided username and password to the configuration of RSS-Bridge
     * (`[authentication] username` and `[authentication] password`).
     *
     * @return bool True if authentication succeeded.
     */
    public static function verifyPrompt()
    {

        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            if (
                Configuration::getConfig('authentication', 'username') === $_SERVER['PHP_AUTH_USER']
                && Configuration::getConfig('authentication', 'password') === $_SERVER['PHP_AUTH_PW']
            ) {
                return true;
            } else {
                error_log('[RSS-Bridge] Failed authentication attempt from ' . $_SERVER['REMOTE_ADDR']);
            }
        }
        return false;
    }
}
