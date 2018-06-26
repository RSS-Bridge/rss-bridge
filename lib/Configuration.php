<?php
class Configuration {
	
	public static $config = null;
	
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

		if(!is_string(Configuration::$config['proxy']['url']))
			die('Parameter [proxy] => "url" is not a valid string! Please check "config.ini.php"!');

		if(!empty(Configuration::$config['proxy']['url']))
			define('PROXY_URL', Configuration::$config['proxy']['url']);

		if(!is_bool(Configuration::$config['proxy']['by_bridge']))
			die('Parameter [proxy] => "by_bridge" is not a valid Boolean! Please check "config.ini.php"!');

		define('PROXY_BYBRIDGE', Configuration::$config['proxy']['by_bridge']);

		if(!is_string(Configuration::$config['proxy']['name']))
			die('Parameter [proxy] => "name" is not a valid string! Please check "config.ini.php"!');

		define('PROXY_NAME', Configuration::$config['proxy']['name']);

		if(!is_bool(Configuration::$config['cache']['custom_timeout']))
			die('Parameter [cache] => "custom_timeout" is not a valid Boolean! Please check "config.ini.php"!');
		
		define('CUSTOM_CACHE_TIMEOUT', Configuration::$config['cache']['custom_timeout']);
		
		if(!is_bool(Configuration::$config['authentication']['enable_authentication']))
			die('Parameter [authentication] => "enable_authentication" is not a valid Boolean! Please check "config.ini.php"!');
		
		if(!is_string(Configuration::$config['authentication']['username']))
			die('Parameter [authentication] => "username" is not a valid string! Please check "config.ini.php"!');
		
		if(!is_string(Configuration::$config['authentication']['password']))
			die('Parameter [authentication] => "password" is not a valid string! Please check "config.ini.php"!');		
		
	}
	
}