<?php
require_once(__DIR__ . '/CacheInterface.php');
class Cache {

	static protected $dirCache;

	public function __construct(){
		throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
	}

	static public function create($nameCache){
		if(!static::isValidNameCache($nameCache)){
			throw new \InvalidArgumentException('Name cache must be at least one
 uppercase follow or not by alphanumeric or dash characters.');
		}

		$pathCache = self::getDir() . $nameCache . '.php';

		if(!file_exists($pathCache)){
			throw new \Exception('The cache you looking for does not exist.');
		}

		require_once $pathCache;

		return new $nameCache();
	}

	static public function setDir($dirCache){
		if(!is_string($dirCache)){
			throw new \InvalidArgumentException('Dir cache must be a string.');
		}

		if(!file_exists($dirCache)){
			throw new \Exception('Dir cache does not exist.');
		}

		self::$dirCache = $dirCache;
	}

	static public function getDir(){
		$dirCache = self::$dirCache;

		if(is_null($dirCache)){
			throw new \LogicException(__CLASS__ . ' class need to know cache path !');
		}

		return $dirCache;
	}

	static public function isValidNameCache($nameCache){
		return preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameCache);
	}
}
