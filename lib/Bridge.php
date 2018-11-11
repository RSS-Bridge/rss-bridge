<?php

class Bridge {

	static protected $dirBridge;

	/**
	 * Holds the active whitelist.
	 * Use Bridge::getWhitelist() instead of accessing this parameter directly!
	 */
	private static $whitelist = array();

	public function __construct(){
		throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
	}

	/**
	* Create a new bridge object
	* @param string $nameBridge Defined bridge name you want use
	* @return Bridge object dedicated
	*/
	static public function create($nameBridge){
		if(!preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameBridge)) {
			$message = <<<EOD
'nameBridge' must start with one uppercase character followed or not by
alphanumeric or dash characters!
EOD;
			throw new \InvalidArgumentException($message);
		}

		$nameBridge = $nameBridge . 'Bridge';
		$pathBridge = self::getDir() . $nameBridge . '.php';

		if(!file_exists($pathBridge)) {
			throw new \Exception('The bridge you looking for does not exist. It should be at path '
			. $pathBridge);
		}

		require_once $pathBridge;

		if((new ReflectionClass($nameBridge))->isInstantiable()) {
			return new $nameBridge();
		}

		return false;
	}

	static public function setDir($dirBridge){
		if(!is_string($dirBridge)) {
			throw new \InvalidArgumentException('Dir bridge must be a string.');
		}

		if(!file_exists($dirBridge)) {
			throw new \Exception('Dir bridge does not exist.');
		}

		self::$dirBridge = $dirBridge;
	}

	static public function getDir(){
		if(is_null(self::$dirBridge)) {
			throw new \LogicException(__CLASS__ . ' class need to know bridge path !');
		}

		return self::$dirBridge;
	}

	/**
	* Lists the available bridges.
	* @return array List of the bridges
	*/
	static public function listBridges(){

		static $listBridge = array(); // Initialized on first call

		if(empty($listBridge)) {
			$dirFiles = scandir(self::getDir());

			if($dirFiles !== false) {
				foreach($dirFiles as $fileName) {
					if(preg_match('@^([^.]+)Bridge\.php$@U', $fileName, $out)) {
						$listBridge[] = $out[1];
					}
				}
			}
		}

		return $listBridge;
	}

	/**
	 * @return bool Returns true if the given bridge is whitelisted.
	 */
	static public function isWhitelisted($name){
		return in_array(Bridge::sanitizeBridgeName($name), Bridge::getWhitelist());
	}

	/**
	 * On first call reads the whitelist from WHITELIST. Each line in the file
	 * specifies one bridge that will be placed on the whitelist. An empty file
	 * disables all bridges. '*' enables all bridges.
	 *
	 * @return array Returns a list of whitelisted bridges
	 */
	public static function getWhitelist() {

		static $firstCall = true; // Initialized on first call

		if($firstCall) {

			// Create initial whitelist or load from disk
			if (!file_exists(WHITELIST) && !empty(Bridge::$whitelist)) {
				file_put_contents(WHITELIST, implode("\n", Bridge::$whitelist));
			} else {

				$contents = trim(file_get_contents(WHITELIST));

				if($contents === '*') { // Whitelist all bridges
					Bridge::$whitelist = Bridge::listBridges();
				} else {
					Bridge::$whitelist = array_map('Bridge::sanitizeBridgeName', explode("\n", $contents));
				}

			}

		}

		return Bridge::$whitelist;

	}

	public static function setWhitelist($default = array()) {
		Bridge::$whitelist = array_map('Bridge::sanitizeBridgeName', $default);
	}

	/**
	 * @return string Returns a sanitized bridge name if the given name has been
	 * found valid, null otherwise.
	 */
	private static function sanitizeBridgeName($name) {

		if(is_string($name)) {

			// Trim trailing '.php' if exists
			if(preg_match('/(.+)(?:\.php)/', $name, $matches)) {
				$name = $matches[1];
			}

			// Trim trailing 'Bridge' if exists
			if(preg_match('/(.+)(?:Bridge)/i', $name, $matches)) {
				$name = $matches[1];
			}

			// The name is valid if a corresponding bridge file is found on disk
			if(in_array(strtolower($name), array_map('strtolower', Bridge::listBridges()))) {
				$index = array_search(strtolower($name), array_map('strtolower', Bridge::listBridges()));
				return Bridge::listBridges()[$index];
			}

			Debug::log('Invalid bridge name specified: "' . $name . '"!');

		}

		return null; // Bad parameter

	}
}
