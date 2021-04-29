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
class FormatFactory extends FactoryAbstract {
	/**
	 * Creates a new format object from the working directory.
	 *
	 * @throws \InvalidArgumentException if the requested format name is invalid.
	 * @throws \Exception if the requested format file doesn't exist in the
	 * working directory.
	 * @param string $name Name of the format object.
	 * @return object|bool The format object or false if the class is not instantiable.
	 */
	public function create($name){
		if(!$this->isFormatName($name)) {
			throw new \InvalidArgumentException('Format name invalid!');
		}

		$name = $this->sanitizeFormatName($name);

		if (is_null($name)) {
			throw new \InvalidArgumentException('Unknown format given!');
		}

		$name .= 'Format';
		$pathFormat = $this->getWorkingDir() . $name . '.php';

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
	 * Returns true if the provided name is a valid format name.
	 *
	 * A valid format name starts with a capital letter ([A-Z]), followed by
	 * zero or more alphanumeric characters or hyphen ([A-Za-z0-9-]).
	 *
	 * @param string $name The format name.
	 * @return bool true if the name is a valid format name, false otherwise.
	 */
	public function isFormatName($name){
		return is_string($name) && preg_match('/^[a-zA-Z0-9-]*$/', $name) === 1;
	}

	/**
	 * Returns the list of format names from the working directory.
	 *
	 * The list is cached internally to allow for successive calls.
	 *
	 * @return array List of format names
	 */
	public function getFormatNames(){
		static $formatNames = array(); // Initialized on first call

		if(empty($formatNames)) {
			$files = scandir($this->getWorkingDir());

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

	/**
	 * Returns the sanitized format name.
	 *
	 * The format name can be specified in various ways:
	 * * The PHP file name (i.e. `AtomFormat.php`)
	 * * The PHP file name without file extension (i.e. `AtomFormat`)
	 * * The format name (i.e. `Atom`)
	 *
	 * A format file matching the given format name must exist in the working
	 * directory!
	 *
	 * @param string $name The format name
	 * @return string|null The sanitized format name if the provided name is
	 * valid, null otherwise.
	 */
	protected function sanitizeFormatName($name) {
		$name = ucfirst(strtolower($name));

		if(is_string($name)) {

			// Trim trailing '.php' if exists
			if(preg_match('/(.+)(?:\.php)/', $name, $matches)) {
				$name = $matches[1];
			}

			// Trim trailing 'Format' if exists
			if(preg_match('/(.+)(?:Format)/i', $name, $matches)) {
				$name = $matches[1];
			}

			// The name is valid if a corresponding format file is found on disk
			if(in_array($name, $this->getFormatNames())) {
				$index = array_search($name, $this->getFormatNames());
				return $this->getFormatNames()[$index];
			}

			Debug::log('Invalid format name: "' . $name . '"!');

		}

		return null; // Bad parameter

	}
}
