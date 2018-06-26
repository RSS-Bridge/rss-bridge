<?php
class Authentication {
	
	public static function showPromptIfNeeded() {
		
		if(Configuration::$config['authentication']['enable_authentication'] == true) {
			if(!Authentication::verifyPrompt()) {
				header('WWW-Authenticate: Basic realm="RSS-Bridge"');
				header('HTTP/1.0 401 Unauthorized');
				die('Please authenticate in order to access this instance !');
			}

		}
		
	}
	
	public static function verifyPrompt() {
		
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			return (Configuration::$config['authentication']['username'] == $_SERVER['PHP_AUTH_USER'] && Configuration::$config['authentication']['password'] == $_SERVER['PHP_AUTH_PW']);
		} else {
			return false;
		}
		
	}
	
}