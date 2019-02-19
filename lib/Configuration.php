<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * Configuration module for RSS-Bridge.
 *
 * This class implements a configuration module for RSS-Bridge.
 */
final class Configuration {

	/**
	 * Holds the current release version of RSS-Bridge.
	 *
	 * Do not access this property directly!
	 * Use {@see Configuration::getVersion()} instead.
	 *
	 * @var string
	 *
	 * @todo Replace this property by a constant.
	 */
	public static $VERSION = 'dev.2019-02-19';

	/**
	 * Holds the configuration data.
	 *
	 * Do not access this property directly!
	 * Use {@see Configuration::getConfig()} instead.
	 *
	 * @var array|null
	 */
	private static $config = null;

	/**
	 * Throw an exception when trying to create a new instance of this class.
	 *
	 * @throws \LogicException if called.
	 */
	public function __construct(){
		throw new \LogicException('Can\'t create object of this class!');
	}

	/**
	 * Verifies the current installation of RSS-Bridge and PHP.
	 *
	 * Returns an error message and aborts execution if the installation does
	 * not satisfy the requirements of RSS-Bridge.
	 *
	 * **Requirements**
	 * - PHP 5.6.0 or higher
	 * - `openssl` extension
	 * - `libxml` extension
	 * - `mbstring` extension
	 * - `simplexml` extension
	 * - `curl` extension
	 * - `json` extension
	 * - The cache folder specified by {@see PATH_CACHE} requires write permission
	 * - The whitelist file specified by {@see WHITELIST} requires write permission
	 *
	 * @link http://php.net/supported-versions.php PHP Supported Versions
	 * @link http://php.net/manual/en/book.openssl.php OpenSSL
	 * @link http://php.net/manual/en/book.libxml.php libxml
	 * @link http://php.net/manual/en/book.mbstring.php Multibyte String (mbstring)
	 * @link http://php.net/manual/en/book.simplexml.php SimpleXML
	 * @link http://php.net/manual/en/book.curl.php Client URL Library (curl)
	 * @link http://php.net/manual/en/book.json.php JavaScript Object Notation (json)
	 *
	 * @return void
	 */
	public static function verifyInstallation() {

		// Check PHP version
		if(version_compare(PHP_VERSION, '5.6.0') === -1)
			die('RSS-Bridge requires at least PHP version 5.6.0!');

		// extensions check
		if(!extension_loaded('openssl'))
			die('"openssl" extension not loaded. Please check "php.ini"');

		if(!extension_loaded('libxml'))
			die('"libxml" extension not loaded. Please check "php.ini"');

		if(!extension_loaded('mbstring'))
			die('"mbstring" extension not loaded. Please check "php.ini"');

		if(!extension_loaded('simplexml'))
			die('"simplexml" extension not loaded. Please check "php.ini"');

		// Allow RSS-Bridge to run without curl module in CLI mode without root certificates
		if(!extension_loaded('curl') && !(php_sapi_name() === 'cli' && empty(ini_get('curl.cainfo'))))
			die('"curl" extension not loaded. Please check "php.ini"');

		if(!extension_loaded('json'))
			die('"json" extension not loaded. Please check "php.ini"');

		// Check cache folder permissions (write permissions required)
		if(!is_writable(PATH_CACHE))
			die('RSS-Bridge does not have write permissions for ' . PATH_CACHE . '!');

		// Check whitelist file permissions
		if(!file_exists(WHITELIST) && !is_writable(dirname(WHITELIST)))
			die('RSS-Bridge does not have write permissions for ' . WHITELIST . '!');

	}

	/**
	 * Loads the configuration from disk and checks if the parameters are valid.
	 *
	 * Returns an error message and aborts execution if the configuration is invalid.
	 *
	 * The RSS-Bridge configuration is split into two files:
	 * - `config.default.ini.php`: The default configuration file that ships with
	 * every release of RSS-Bridge (do not modify this file!).
	 * - `config.ini.php`: The local configuration file that can be modified by
	 * server administrators.
	 *
	 * The files must be located at {@see PATH_ROOT}
	 *
	 * RSS-Bridge will first load `config.default.ini.php` into memory and then
	 * replace parameters with the contents of `config.ini.php`. That way new
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
	public static function loadConfiguration() {

		if(!file_exists(PATH_ROOT . 'config.default.ini.php'))
			die('The default configuration file "config.default.ini.php" is missing!');

		Configuration::$config = parse_ini_file(PATH_ROOT . 'config.default.ini.php', true, INI_SCANNER_TYPED);
		if(!Configuration::$config)
			die('Error parsing config.default.ini.php');

		if(file_exists(PATH_ROOT . 'config.ini.php')) {
			// Replace default configuration with custom settings
			foreach(parse_ini_file(PATH_ROOT . 'config.ini.php', true, INI_SCANNER_TYPED) as $header => $section) {
				foreach($section as $key => $value) {
					// Skip unknown sections and keys
					if(array_key_exists($header, Configuration::$config) && array_key_exists($key, Configuration::$config[$header])) {
						Configuration::$config[$header][$key] = $value;
					}
				}
			}
		}

		if(!is_string(self::getConfig('proxy', 'url')))
			die('Parameter [proxy] => "url" is not a valid string! Please check "config.ini.php"!');

		if(!empty(self::getConfig('proxy', 'url'))) {
			/** URL of the proxy server */
			define('PROXY_URL', self::getConfig('proxy', 'url'));
		}

		if(!is_bool(self::getConfig('proxy', 'by_bridge')))
			die('Parameter [proxy] => "by_bridge" is not a valid Boolean! Please check "config.ini.php"!');

		/** True if proxy usage can be enabled selectively for each bridge */
		define('PROXY_BYBRIDGE', self::getConfig('proxy', 'by_bridge'));

		if(!is_string(self::getConfig('proxy', 'name')))
			die('Parameter [proxy] => "name" is not a valid string! Please check "config.ini.php"!');

		/** Name of the proxy server */
		define('PROXY_NAME', self::getConfig('proxy', 'name'));

		if(!is_string(self::getConfig('cache', 'type')))
			die('Parameter [cache] => "type" is not a valid string! Please check "config.ini.php"!');

		if(!is_bool(self::getConfig('cache', 'custom_timeout')))
			die('Parameter [cache] => "custom_timeout" is not a valid Boolean! Please check "config.ini.php"!');

		/** True if the cache timeout can be specified by the user */
		define('CUSTOM_CACHE_TIMEOUT', self::getConfig('cache', 'custom_timeout'));

		if(!is_bool(self::getConfig('authentication', 'enable')))
			die('Parameter [authentication] => "enable" is not a valid Boolean! Please check "config.ini.php"!');

		if(!is_string(self::getConfig('authentication', 'username')))
			die('Parameter [authentication] => "username" is not a valid string! Please check "config.ini.php"!');

		if(!is_string(self::getConfig('authentication', 'password')))
			die('Parameter [authentication] => "password" is not a valid string! Please check "config.ini.php"!');

		if(!empty(self::getConfig('admin', 'email'))
		&& !filter_var(self::getConfig('admin', 'email'), FILTER_VALIDATE_EMAIL))
			die('Parameter [admin] => "email" is not a valid email address! Please check "config.ini.php"!');

	}

	/**
	 * Returns the value of a parameter identified by section and key.
	 *
	 * @param string $section The section name.
	 * @param string $key The property name (key).
	 * @return mixed|null The parameter value.
	 */
	public static function getConfig($section, $key) {

		if(array_key_exists($section, self::$config) && array_key_exists($key, self::$config[$section])) {
			return self::$config[$section][$key];
		}

		return null;

	}

	/**
	 * Returns the current version string of RSS-Bridge.
	 *
	 * This function returns the contents of {@see Configuration::$VERSION} for
	 * regular installations and the git branch name and commit id for instances
	 * running in a git environment.
	 *
	 * @return string The version string.
	 */
	public static function getVersion() {

		$headFile = PATH_ROOT . '.git/HEAD';

		// '@' is used to mute open_basedir warning
		if(@is_readable($headFile)) {

			$revisionHashFile = '.git/' . substr(file_get_contents($headFile), 5, -1);
			$branchName = explode('/', $revisionHashFile)[3];
			if(file_exists($revisionHashFile)) {
				return 'git.' . $branchName . '.' . substr(file_get_contents($revisionHashFile), 0, 7);
			}
		}

		return Configuration::$VERSION;

	}
}
