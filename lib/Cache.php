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
 * - Locating cache classes in the specified working directory (see {@see Cache::$workingDir})
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
	 * Holds a path to the working directory.
	 *
	 * Do not access this property directly!
	 * Use {@see Cache::setDir()} and {@see Cache::getDir()} instead.
	 *
	 * @var string|null
	 */
	static protected $workingDir = null;

	/**
	 * Throws an exception when trying to create a new instance of this class.
	 * Use {@see Cache::create()} to create a new cache object from the working
	 * directory.
	 *
	 * @throws \LogicException if called.
	 */
	public function __construct(){
		throw new \LogicException('Use ' . __CLASS__ . '::create($name) to create cache objects!');
	}

	/**
	 * Creates a new cache object from the working directory.
	 *
	 * @throws \InvalidArgumentException if the requested cache name is invalid.
	 * @throws \Exception if the requested cache file doesn't exist in the
	 * working directory.
	 * @param string $name Name of the cache object.
	 * @return object The cache object.
	 */
	public static function create($name){
		if(!self::isCacheName($name)) {
			throw new \InvalidArgumentException('Cache name invalid!');
		}

		$filePath = self::getDir() . $name . '.php';

		if(!file_exists($filePath)) {
			throw new \Exception('Cache file ' . $filePath . ' does not exist!');
		}

		require_once $filePath;

		return new $name();
	}

	/**
	 * Sets the working directory.
	 *
	 * @param string $workingDir Path to a directory containing cache classes
	 * @throws \InvalidArgumentException if $workingDir is not a string.
	 * @throws \Exception if the working directory doesn't exist.
	 * @throws \InvalidArgumentException if $workingDir is not a directory.
	 * @return void
	 */
	public static function setDir($workingDir){
		self::$workingDir = null;

		if(!is_string($workingDir)) {
			throw new \InvalidArgumentException('Working directory is not a valid string!');
		}

		if(!file_exists($workingDir)) {
			throw new \Exception('Working directory does not exist!');
		}

		if(!is_dir($workingDir)) {
			throw new \InvalidArgumentException('Working directory is not a directory!');
		}

		self::$workingDir = realpath($workingDir) . '/';
	}

	/**
	 * Returns the current working directory.
	 * The working directory must be set with {@see Cache::setDir()}!
	 *
	 * @throws \LogicException if the working directory is not set.
	 * @return string The current working directory.
	 */
	public static function getDir(){
		if(is_null(self::$workingDir)) {
			throw new \LogicException('Working directory is not set!');
		}

		return self::$workingDir;
	}

	/**
	 * Returns true if the provided name is a valid cache name.
	 *
	 * A valid cache name starts with a capital letter ([A-Z]), followed by
	 * zero or more alphanumeric characters or hyphen ([A-Za-z0-9-]).
	 *
	 * @param string $name The cache name.
	 * @return bool true if the name is a valid cache name, false otherwise.
	 */
	public static function isCacheName($name){
		return is_string($name) && preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name) === 1;
	}
}
