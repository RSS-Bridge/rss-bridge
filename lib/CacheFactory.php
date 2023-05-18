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
 * Cache::setWorkingDir(__DIR__ . '/../caches/');
 *
 * // Step 2: Create a new instance of a cache object (based on the name)
 * $cache = Cache::create('FileCache');
 * ```
 */
class CacheFactory extends FactoryAbstract {
	/**
	 * Creates a new cache object from the working directory.
	 *
	 * @throws \InvalidArgumentException if the requested cache name is invalid.
	 * @throws \Exception if the requested cache file doesn't exist in the
	 * working directory.
	 * @param string $name Name of the cache object.
	 * @return object|bool The cache object or false if the class is not instantiable.
	 */
	public function create($name){
		$name = $this->sanitizeCacheName($name) . 'Cache';

		if(!$this->isCacheName($name)) {
			throw new \InvalidArgumentException('Cache name invalid!');
		}

		$filePath = $this->getWorkingDir() . $name . '.php';

		if(!file_exists($filePath)) {
			throw new \Exception('Cache file ' . $filePath . ' does not exist!');
		}

		if((new \ReflectionClass($name))->isInstantiable()) {
			return new $name();
		}

		return false;
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
	public function isCacheName($name){
		return is_string($name) && preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name) === 1;
	}

	/**
	 * Returns a list of cache names from the working directory.
	 *
	 * The list is cached internally to allow for successive calls.
	 *
	 * @return array List of cache names
	 */
	public function getCacheNames(){

		static $cacheNames = array(); // Initialized on first call

		if(empty($cacheNames)) {
			$files = scandir($this->getWorkingDir());

			if($files !== false) {
				foreach($files as $file) {
					if(preg_match('/^([^.]+)Cache\.php$/U', $file, $out)) {
						$cacheNames[] = $out[1];
					}
				}
			}
		}

		return $cacheNames;
	}

	/**
	 * Returns the sanitized cache name.
	 *
	 * The cache name can be specified in various ways:
	 * * The PHP file name (i.e. `FileCache.php`)
	 * * The PHP file name without file extension (i.e. `FileCache`)
	 * * The cache name (i.e. `file`)
	 *
	 * Casing is ignored (i.e. `FILE` and `fIlE` are the same).
	 *
	 * A cache file matching the given cache name must exist in the working
	 * directory!
	 *
	 * @param string $name The cache name
	 * @return string|null The sanitized cache name if the provided name is
	 * valid, null otherwise.
	 */
	protected function sanitizeCacheName($name) {

		if(is_string($name)) {

			// Trim trailing '.php' if exists
			if(preg_match('/(.+)(?:\.php)/', $name, $matches)) {
				$name = $matches[1];
			}

			// Trim trailing 'Cache' if exists
			if(preg_match('/(.+)(?:Cache)$/i', $name, $matches)) {
				$name = $matches[1];
			}

			// The name is valid if a corresponding file is found on disk
			if(in_array(strtolower($name), array_map('strtolower', $this->getCacheNames()))) {
				$index = array_search(strtolower($name), array_map('strtolower', $this->getCacheNames()));
				return $this->getCacheNames()[$index];
			}

			Debug::log('Invalid cache name specified: "' . $name . '"!');

		}

		return null; // Bad parameter

	}
}
