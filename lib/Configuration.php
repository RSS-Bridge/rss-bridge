<?php
class Configuration {

	public static $VERSION = 'dev.2018-11-10';

	public static $config = null;

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

		if(!extension_loaded('curl'))
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

	public static function loadConfiguration() {

		if(!file_exists('config.default.ini.php'))
			die('The default configuration file "config.default.ini.php" is missing!');

		Configuration::$config = parse_ini_file('config.default.ini.php', true, INI_SCANNER_TYPED);
		if(!Configuration::$config)
			die('Error parsing config.default.ini.php');

		if(file_exists('config.ini.php')) {
			// Replace default configuration with custom settings
			foreach(parse_ini_file('config.ini.php', true, INI_SCANNER_TYPED) as $header => $section) {
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

		if(!empty(self::getConfig('proxy', 'url')))
			define('PROXY_URL', self::getConfig('proxy', 'url'));

		if(!is_bool(self::getConfig('proxy', 'by_bridge')))
			die('Parameter [proxy] => "by_bridge" is not a valid Boolean! Please check "config.ini.php"!');

		define('PROXY_BYBRIDGE', self::getConfig('proxy', 'by_bridge'));

		if(!is_string(self::getConfig('proxy', 'name')))
			die('Parameter [proxy] => "name" is not a valid string! Please check "config.ini.php"!');

		define('PROXY_NAME', self::getConfig('proxy', 'name'));

		if(!is_bool(self::getConfig('cache', 'custom_timeout')))
			die('Parameter [cache] => "custom_timeout" is not a valid Boolean! Please check "config.ini.php"!');

		define('CUSTOM_CACHE_TIMEOUT', self::getConfig('cache', 'custom_timeout'));

		if(!is_bool(self::getConfig('cache', 'ignore_custom_timeout')))
			die('Parameter [cache] => "ignore_custom_timeout" is not a valid Boolean! Please check "config.ini.php"!');

		define('IGNORE_CUSTOM_CACHE_TIMEOUT', self::getConfig('cache', 'ignore_custom_timeout'));

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

	public static function getConfig($category, $key) {

		if(array_key_exists($category, self::$config) && array_key_exists($key, self::$config[$category])) {
			return self::$config[$category][$key];
		}

		return null;

	}

	public static function getVersion() {

		$headFile = '.git/HEAD';

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
