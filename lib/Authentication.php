<?php
class Authentication {

	public static function showPromptIfNeeded() {

		if(Configuration::getConfig('authentication', 'enable') === true) {
			if(!Authentication::verifyPrompt()) {
				header('WWW-Authenticate: Basic realm="RSS-Bridge"');
				header('HTTP/1.0 401 Unauthorized');
				die('Please authenticate in order to access this instance !');
			}

		}

	}

	public static function verifyPrompt() {

		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			if(Configuration::getConfig('authentication', 'username') === $_SERVER['PHP_AUTH_USER']
				&& Configuration::getConfig('authentication', 'password') === $_SERVER['PHP_AUTH_PW']) {
				return true;
			} else {
				error_log('[RSS-Bridge] Failed authentication attempt from ' . $_SERVER['REMOTE_ADDR']);
			}
		}
		return false;

	}

}
