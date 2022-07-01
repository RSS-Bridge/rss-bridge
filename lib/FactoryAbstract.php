<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/**
 * Abstract class for factories.
 */
abstract class FactoryAbstract
{
    /**
     * Holds the working directory
     *
     * @var string
     */
    private $workingDir = null;

    /**
     * Set the working directory.
     *
     * @param string $dir The working directory.
     * @return void
     */
    public function setWorkingDir($dir)
    {
        $this->workingDir = null;

        if (!is_string($dir)) {
            throw new \InvalidArgumentException('Working directory must be a string!');
        }

        if (!file_exists($dir)) {
            throw new \Exception('Working directory does not exist!');
        }

        if (!is_dir($dir)) {
            throw new \InvalidArgumentException($dir . ' is not a directory!');
        }

        $this->workingDir = realpath($dir) . '/';
    }

    /**
     * Get the working directory
     *
     * @return string The working directory.
     */
    public function getWorkingDir()
    {
        if (is_null($this->workingDir)) {
            throw new \LogicException('Working directory is not set!');
        }

        return $this->workingDir;
    }

    /**
     * Creates a new instance for the object specified by name.
     *
     * @param string $name The name of the object to create.
     * @return object The object instance
     */
    abstract public function create($name);
}
