<?php
require_once(__DIR__ . '/BridgeInterface.php');
class Bridge {

	static protected $dirBridge;

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
		$listBridge = array();
		$dirFiles = scandir(self::getDir());

		if($dirFiles !== false) {
			foreach($dirFiles as $fileName) {
				if(preg_match('@^([^.]+)Bridge\.php$@U', $fileName, $out)) {
					$listBridge[] = $out[1];
				}
			}
		}

		return $listBridge;
	}

	static public function isWhitelisted($whitelist, $name){
		return in_array($name, $whitelist)
		|| in_array($name . '.php', $whitelist)
		|| in_array($name . 'bridge', $whitelist) // DEPRECATED
		|| in_array($name . 'bridge.php', $whitelist) // DEPRECATED
		|| (count($whitelist) === 1 && trim($whitelist[0]) === '*');
	}
}
