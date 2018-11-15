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
 * Factory class responsible for creating format objects from a given working
 * directory.
 *
 * This class is capable of:
 * - Locating format classes in the specified working directory (see {@see Format::$workingDir})
 * - Creating new format instances based on the format's name (see {@see Format::create()})
 *
 * The following example illustrates the intended use for this class.
 *
 * ```PHP
 * require_once __DIR__ . '/rssbridge.php';
 *
 * // Step 1: Set the working directory
 * Format::setWorkingDir(__DIR__ . '/../formats/');
 *
 * // Step 2: Create a new instance of a format object (based on the name)
 * $format = Format::create('Atom');
 * ```
 */
class Format {

	/**
	 * Holds a path to the working directory.
	 *
	 * Do not access this property directly!
	 * Use {@see Format::setWorkingDir()} and {@see Format::getWorkingDir()} instead.
	 *
	 * @var string|null
	 */
	protected static $workingDir = null;

	/**
	 * Throws an exception when trying to create a new instance of this class.
	 * Use {@see Format::create()} to create a new format object from the working
	 * directory.
	 *
	 * @throws \LogicException if called.
	 */
	public function __construct(){
		throw new \LogicException('Use ' . __CLASS__ . '::create($name) to create cache objects!');
	}

	/**
	 * Creates a new format object from the working directory.
	 *
	 * @throws \InvalidArgumentException if the requested format name is invalid.
	 * @throws \Exception if the requested format file doesn't exist in the
	 * working directory.
	 * @param string $name Name of the format object.
	 * @return object|bool The format object or false if the class is not instantiable.
	 */
	public static function create($name){
		if(!self::isFormatName($name)) {
			throw new \InvalidArgumentException('Format name invalid!');
		}

		$name = $name . 'Format';
		$pathFormat = self::getWorkingDir() . $name . '.php';

		if(!file_exists($pathFormat)) {
			throw new \Exception('Format file ' . $filePath . ' does not exist!');
		}

		require_once $pathFormat;

		if((new \ReflectionClass($name))->isInstantiable()) {
			return new $name();
		}

		return false;
	}

	/**
	 * Sets the working directory.
	 *
	 * @param string $dir Path to a directory containing cache classes
	 * @throws \InvalidArgumentException if $dir is not a string.
	 * @throws \Exception if the working directory doesn't exist.
	 * @throws \InvalidArgumentException if $dir is not a directory.
	 * @return void
	 */
	public static function setWorkingDir($dir){
		self::$workingDir = null;

		if(!is_string($dir)) {
			throw new \InvalidArgumentException('Dir format must be a string.');
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
	 * The working directory must be set with {@see Format::setWorkingDir()}!
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
	 * Returns true if the provided name is a valid format name.
	 *
	 * A valid format name starts with a capital letter ([A-Z]), followed by
	 * zero or more alphanumeric characters or hyphen ([A-Za-z0-9-]).
	 *
	 * @param string $name The format name.
	 * @return bool true if the name is a valid format name, false otherwise.
	 */
	public static function isFormatName($name){
		return is_string($name) && preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name) === 1;
	}

	/**
	 * Returns the list of format names from the working directory.
	 *
	 * The list is cached internally to allow for successive calls.
	 *
	 * @return array List of format names
	 */
	public static function getFormatNames(){
		static $formatNames = array(); // Initialized on first call

		if(empty($formatNames)) {
			$files = scandir(self::getWorkingDir());

			if($files !== false) {
				foreach($files as $file) {
					if(preg_match('/^([^.]+)Format\.php$/U', $file, $out)) {
						$formatNames[] = $out[1];
					}
				}
			}
		}

		return $formatNames;
	}
}
