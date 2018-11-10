<?php

/**
 * Implements functions for debugging purposes. Debugging can be enabled by
 * placing a file named 'DEBUG' in PATH_ROOT.
 *
 * The file specifies a whitelist of IP addresses on which debug mode will be
 * enabled. An empty file enables debug mode for everyone (highly discouraged
 * for public servers!). Each line in the file specifies one client in the
 * whitelist. For example:
 *
 * 192.168.1.72
 * 127.0.0.1
 * ::1
 *
 * Notice: If you are running RSS-Bridge on your local machine, you need to add
 * localhost (either 127.0.0.1 for IPv4 or ::1 for IPv6) to your whitelist!
 *
 * Warning: In debug mode your server may display sensitive information! For
 * security reasons it is recommended to whitelist only specific IP addresses.
 */
class Debug {

	/**
	 * Indicates if debug mode is enabled.
	 * Use Debug::isEnabled() instead of accessing this parameter directly!
	 */
	private static $enabled = false;

	/**
	 * Indicates if debug mode is secure (not enabled for everyone).
	 * Use Debug::isSecure() instead of accessing this parameter directly!
	 */
	private static $secure = false;

	/**
	 * @return bool Indicates if debug mode is enabled
	 */
	public static function isEnabled() {
		static $firstCall = true; // Initialized on first call

		if($firstCall && file_exists(PATH_ROOT . 'DEBUG')) {

			$debug_whitelist = trim(file_get_contents(PATH_ROOT . 'DEBUG'));

			Debug::$enabled = empty($debug_whitelist) || in_array($_SERVER['REMOTE_ADDR'],
					explode("\n", str_replace("\r", '', $debug_whitelist)
				)
			);

			if(Debug::$enabled) {
				ini_set('display_errors', '1');
				error_reporting(E_ALL);

				Debug::$secure = !empty($debug_whitelist);
			}

			$firstCall = false; // Skip check on next call

		}

		return Debug::$enabled;
	}

	/**
	 * Returns true if debug mode has been enabled for specific IP addresses
	 * only, false otherwise.
	 *
	 * Notice: The security flag is set by Debug::isEnabled(). If this function
	 * is called before Debug::isEnabled(), the default value is false!
	 *
	 * @return bool Indicates if debug mode is secure
	 */
	public static function isSecure() {
		return Debug::$secure;
	}

	/**
	 * Adds a debug message to error_log if debug mode is enabled
	 */
	public static function log($text) {
		if(!Debug::isEnabled()) {
			return;
		}

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$calling = $backtrace[2];
		$message = $calling['file'] . ':'
			. $calling['line'] . ' class '
			. (isset($calling['class']) ? $calling['class'] : '<no-class>') . '->'
			. $calling['function'] . ' - '
			. $text;

		error_log($message);
	}
}
