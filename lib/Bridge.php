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
	 * @var string
	 */
	static protected $workingDir;

	/**
	 * Holds a list of whitelisted bridges.
	 *
	 * Do not access this property directly!
	 * Use {@see Bridge::getWhitelist()} instead.
	 *
	 * @var array
	 */
	private static $whitelist = array();

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
	 * @param string $nameBridge Name of the bridge object.
	 * @return object|bool The bridge object or false if the class is not instantiable.
	 */
	public static function create($nameBridge){
		if(!preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameBridge)) {
			throw new \InvalidArgumentException('Bridge name invalid!');
		}

		$nameBridge = Bridge::sanitizeBridgeName($nameBridge) . 'Bridge';
		$pathBridge = self::getWorkingDir() . $nameBridge . '.php';

		if(!file_exists($pathBridge)) {
			throw new \Exception('Cache file ' . $pathBridge . ' does not exist!');
		}

		require_once $pathBridge;

		if((new ReflectionClass($nameBridge))->isInstantiable()) {
			return new $nameBridge();
		}

		return false;
	}

	/**
	 * Sets the working directory.
	 *
	 * @param string $dir Path to the directory containing bridges.
	 * @throws \LogicException if the provided path is not a valid string.
	 * @throws \Exception if the provided path does not exist.
	 * @return void
	 */
	public static function setWorkingDir($dir){
		if(!is_string($dir)) {
			throw new \InvalidArgumentException('Working directory is not a valid string!');
		}

		if(!file_exists($dir)) {
			throw new \Exception('Working directory does not exist!');
		}

		self::$workingDir = $dir;
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
	 * Returns the list of bridge names based on the working directory.
	 *
	 * The list is cached internally to allow for successive calls.
	 *
	 * @return array List of bridge names
	 */
	public static function listBridges(){

		static $listBridge = array(); // Initialized on first call

		if(empty($listBridge)) {
			$dirFiles = scandir(self::getWorkingDir());

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
	 * Checks if a bridge is whitelisted.
	 *
	 * @param string $name Name of the bridge.
	 * @return bool True if the bridge is whitelisted.
	 */
	public static function isWhitelisted($name){
		return in_array(Bridge::sanitizeBridgeName($name), Bridge::getWhitelist());
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
		Bridge::$whitelist = array_map('Bridge::sanitizeBridgeName', $default);
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
