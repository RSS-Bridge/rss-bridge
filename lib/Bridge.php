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
 * Factory class responsible for creating bridge objects from a given working
 * directory, limited by a whitelist.
 *
 * This class is capable of:
 * - Locating bridge classes in the specified working directory (see {@see Bridge::$workingDir})
 * - Filtering bridges based on a whitelist (see {@see Bridge::$whitelist})
 * - Creating new bridge instances based on the bridge's name (see {@see Bridge::create()})
 *
 * The following example illustrates the intended use for this class.
 *
 * ```PHP
 * require_once __DIR__ . '/rssbridge.php';
 *
 * // Step 1: Set the working directory
 * Bridge::setWorkingDir(__DIR__ . '/../bridges/');
 *
 * // Step 2: Add bridges to the whitelist
 * Bridge::setWhitelist(array('GitHubIssue', 'GoogleSearch', 'Facebook', 'Twitter'));
 *
 * // Step 3: Create a new instance of a bridge (based on the name)
 * $bridge = Bridge::create('GitHubIssue');
 * ```
 */
class Bridge {

	/**
	 * Holds a path to the working directory.
	 *
	 * Do not access this property directly!
	 * Use {@see Bridge::setWorkingDir()} and {@see Bridge::getWorkingDir()} instead.
	 *
	 * @var string|null
	 */
	protected static $workingDir = null;

	/**
	 * Holds a list of whitelisted bridges.
	 *
	 * Do not access this property directly!
	 * Use {@see Bridge::getWhitelist()} instead.
	 *
	 * @var array
	 */
	protected static $whitelist = array();

	/**
	 * Throws an exception when trying to create a new instance of this class.
	 * Use {@see Bridge::create()} to instanciate a new bridge from the working
	 * directory.
	 *
	 * @throws \LogicException if called.
	 */
	public function __construct(){
		throw new \LogicException('Use ' . __CLASS__ . '::create($name) to create bridge objects!');
	}

	/**
	 * Creates a new bridge object from the working directory.
	 *
	 * @throws \InvalidArgumentException if the requested bridge name is invalid.
	 * @throws \Exception if the requested bridge doesn't exist in the working
	 * directory.
	 * @param string $name Name of the bridge object.
	 * @return object|bool The bridge object or false if the class is not instantiable.
	 */
	public static function create($name){
		if(!self::isBridgeName($name)) {
			throw new \InvalidArgumentException('Bridge name invalid!');
		}

		$name = self::sanitizeBridgeName($name) . 'Bridge';
		$filePath = self::getWorkingDir() . $name . '.php';

		if(!file_exists($filePath)) {
			throw new \Exception('Bridge file ' . $filePath . ' does not exist!');
		}

		require_once $filePath;

		if((new \ReflectionClass($name))->isInstantiable()) {
			return new $name();
		}

		return false;
	}

	/**
	 * Sets the working directory.
	 *
	 * @param string $dir Path to the directory containing bridges.
	 * @throws \LogicException if the provided path is not a valid string.
	 * @throws \Exception if the provided path does not exist.
	 * @throws \InvalidArgumentException if $dir is not a directory.
	 * @return void
	 */
	public static function setWorkingDir($dir){
		self::$workingDir = null;

		if(!is_string($dir)) {
			throw new \InvalidArgumentException('Working directory is not a valid string!');
		}

		if(!file_exists($dir)) {
			throw new \Exception('Working directory does not exist!');
		}

		if(!is_dir($dir)) {
			throw new \InvalidArgumentException('Working directory is not a directory!');
		}

		self::$workingDir = realpath($dir) . '/';
	}

	/**
	 * Returns the working directory.
	 * The working directory must be specified with {@see Bridge::setWorkingDir()}!
	 *
	 * @throws \LogicException if the working directory is not set.
	 * @return string The current working directory.
	 */
	public static function getWorkingDir(){
		if(is_null(self::$workingDir)) {
			throw new \LogicException('Working directory is not set!');
		}

		return self::$workingDir;
	}

	/**
	 * Returns true if the provided name is a valid bridge name.
	 *
	 * A valid bridge name starts with a capital letter ([A-Z]), followed by
	 * zero or more alphanumeric characters or hyphen ([A-Za-z0-9-]).
	 *
	 * @param string $name The bridge name.
	 * @return bool true if the name is a valid bridge name, false otherwise.
	 */
	public static function isBridgeName($name){
		return is_string($name) && preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name) === 1;
	}

	/**
	 * Returns the list of bridge names from the working directory.
	 *
	 * The list is cached internally to allow for successive calls.
	 *
	 * @return array List of bridge names
	 */
	public static function getBridgeNames(){

		static $bridgeNames = array(); // Initialized on first call

		if(empty($bridgeNames)) {
			$files = scandir(self::getWorkingDir());

			if($files !== false) {
				foreach($files as $file) {
					if(preg_match('/^([^.]+)Bridge\.php$/U', $file, $out)) {
						$bridgeNames[] = $out[1];
					}
				}
			}
		}

		return $bridgeNames;
	}

	/**
	 * Checks if a bridge is whitelisted.
	 *
	 * @param string $name Name of the bridge.
	 * @return bool True if the bridge is whitelisted.
	 */
	public static function isWhitelisted($name){
		return in_array(self::sanitizeBridgeName($name), self::getWhitelist());
	}

	/**
	 * Returns the whitelist.
	 *
	 * On first call this function reads the whitelist from {@see WHITELIST}.
	 * * Each line in the file specifies one bridge on the whitelist.
	 * * An empty file disables all bridges.
	 * * If the file only only contains `*`, all bridges are whitelisted.
	 *
	 * Use {@see Bridge::setWhitelist()} to specify a default whitelist **before**
	 * calling this function! The list is cached internally to allow for
	 * successive calls. If {@see Bridge::setWhitelist()} gets called after this
	 * function, the whitelist is **not** updated again!
	 *
	 * @return array Array of whitelisted bridges
	 */
	public static function getWhitelist() {

		static $firstCall = true; // Initialized on first call

		if($firstCall) {

			// Create initial whitelist or load from disk
			if (!file_exists(WHITELIST) && !empty(self::$whitelist)) {
				file_put_contents(WHITELIST, implode("\n", self::$whitelist));
			} elseif(file_exists(WHITELIST)) {

				$contents = trim(file_get_contents(WHITELIST));

				if($contents === '*') { // Whitelist all bridges
					self::$whitelist = self::getBridgeNames();
				} else {
					self::$whitelist = array_map('self::sanitizeBridgeName', explode("\n", $contents));
				}

			}

		}

		return self::$whitelist;

	}

	/**
	 * Sets the (default) whitelist.
	 *
	 * If this function is called **before** {@see Bridge::getWhitelist()}, the
	 * provided whitelist will be replaced by a custom whitelist specified in
	 * {@see WHITELIST} (if it exists).
	 *
	 * If this function is called **after** {@see Bridge::getWhitelist()}, the
	 * provided whitelist is taken as is (not updated by the custom whitelist
	 * again).
	 *
	 * @param array $default The whitelist as array of bridge names.
	 * @return void
	 */
	public static function setWhitelist($default = array()) {
		self::$whitelist = array_map('self::sanitizeBridgeName', $default);
	}

	/**
	 * Returns the sanitized bridge name.
	 *
	 * The bridge name can be specified in various ways:
	 * * The PHP file name (i.e. `GitHubIssueBridge.php`)
	 * * The PHP file name without file extension (i.e. `GitHubIssueBridge`)
	 * * The bridge name (i.e. `GitHubIssue`)
	 *
	 * Casing is ignored (i.e. `GITHUBISSUE` and `githubissue` are the same).
	 *
	 * A bridge file matching the given bridge name must exist in the working
	 * directory!
	 *
	 * @param string $name The bridge name
	 * @return string|null The sanitized bridge name if the provided name is
	 * valid, null otherwise.
	 */
	protected static function sanitizeBridgeName($name) {

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
			if(in_array(strtolower($name), array_map('strtolower', self::getBridgeNames()))) {
				$index = array_search(strtolower($name), array_map('strtolower', self::getBridgeNames()));
				return self::getBridgeNames()[$index];
			}

			Debug::log('Invalid bridge name specified: "' . $name . '"!');

		}

		return null; // Bad parameter

	}
}
