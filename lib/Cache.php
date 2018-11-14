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
 * Factory class responsible for creating cache objects from a given working
 * directory.
 *
 * This class is capable of:
 * - Locating cache classes in the specified working directory (see {@see Cache::$dirCache})
 * - Creating new cache instances based on the cache's name (see {@see Cache::create()})
 *
 * The following example illustrates the intended use for this class.
 *
 * ```PHP
 * require_once __DIR__ . '/rssbridge.php';
 *
 * // Step 1: Set the working directory
 * Cache::setDir(__DIR__ . '/../caches/');
 *
 * // Step 2: Create a new instance of a cache object (based on the name)
 * $cache = Cache::create('FileCache');
 * ```
 */
class Cache {

	/**
	 * Holds the working directory.
	 *
	 * Do not access this property directly!
	 * Use {@see Cache::setDir()} and {@see Cache::getDir()} instead.
	 *
	 * @var string
	 */
	static protected $dirCache;

	/**
	 * Throws an exception when trying to create a new instance of this class.
	 * Use {@see Cache::create()} to instanciate a new cache from the working
	 * directory.
	 *
	 * @throws LogicException if called.
	 */
	public function __construct(){
		throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
	}

	/**
	 * Creates a new cache object from the working directory.
	 *
	 * @throws InvalidArgumentException if the provided name is invalid.
	 * @throws Exception if no cache with the given name exist in the working
	 * directory.
	 * @param string $nameCache Name of the cache object.
	 * @return object Instance of the cache.
	 */
	public static function create($nameCache){
		if(!static::isValidNameCache($nameCache)) {
			throw new \InvalidArgumentException('Name cache must be at least one
 uppercase follow or not by alphanumeric or dash characters.');
		}

		$pathCache = self::getDir() . $nameCache . '.php';

		if(!file_exists($pathCache)) {
			throw new \Exception('The cache you are looking for does not exist.');
		}

		require_once $pathCache;

		return new $nameCache();
	}

	/**
	 * Sets the working directory.
	 *
	 * @param string $dirCache Path to a directory containing cache classes
	 * @throws InvalidArgumentException if the provided path is not a valid string.
	 * @throws Exception if the provided path does not exist.
	 * @return void
	 */
	public static function setDir($dirCache){
		if(!is_string($dirCache)) {
			throw new \InvalidArgumentException('Dir cache must be a string.');
		}

		if(!file_exists($dirCache)) {
			throw new \Exception('Dir cache does not exist.');
		}

		self::$dirCache = $dirCache;
	}

	/**
	 * Returns the current working directory.
	 * The working directory must be specified with {@see Cache::setDir()}!
	 *
	 * @throws LogicException if the working directory was not specified.
	 * @return string The current working directory.
	 */
	public static function getDir(){
		$dirCache = self::$dirCache;

		if(is_null($dirCache)) {
			throw new \LogicException(__CLASS__ . ' class need to know cache path !');
		}

		return $dirCache;
	}

	/**
	 * Returns true if the provided name is a valid cache name.
	 *
	 * @param string $nameCache The cache name.
	 * @return int 1 if the name is valid, 0 if not, false if an error occurred.
	 */
	public static function isValidNameCache($nameCache){
		return preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameCache);
	}
}
