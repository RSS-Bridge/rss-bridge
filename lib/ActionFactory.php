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
 * Factory for action objects.
 */
class ActionFactory extends FactoryAbstract {
	/**
	 * {@inheritdoc}
	 *
	 * @param string $name {@inheritdoc}
	 */
	public function create($name) {
		$filePath = $this->buildFilePath($name);

		if(!file_exists($filePath)) {
			throw new \Exception('File ' . $filePath . ' does not exist!');
		}

		require_once $filePath;

		$class = $this->buildClassName($name);

		if((new \ReflectionClass($class))->isInstantiable()) {
			return new $class();
		}

		return false;
	}

	/**
	 * Build class name from action name
	 *
	 * The class name consists of the action name with prefix "Action". The first
	 * character of the class name must be uppercase.
	 *
	 * Example: 'display' => 'DisplayAction'
	 *
	 * @param string $name The action name.
	 * @return string The class name.
	 */
	protected function buildClassName($name) {
		return ucfirst(strtolower($name)) . 'Action';
	}

	/**
	 * Build file path to the action class.
	 *
	 * @param string $name The action name.
	 * @return string Path to the action class.
	 */
	protected function buildFilePath($name) {
		return $this->getWorkingDir() . $this->buildClassName($name) . '.php';
	}
}
