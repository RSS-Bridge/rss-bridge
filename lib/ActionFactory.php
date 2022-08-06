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

class ActionFactory
{
    private $folder;

    public function __construct(string $folder = PATH_LIB_ACTIONS)
    {
        $this->folder = $folder;
    }

    /**
     * @param string $name The name of the action e.g. "Display", "List", or "Connectivity"
     */
    public function create(string $name): ActionInterface
    {
        $name = strtolower($name) . 'Action';
        $name = implode(array_map('ucfirst', explode('-', $name)));
        $filePath = $this->folder . $name . '.php';
        if (!file_exists($filePath)) {
            throw new \Exception('Invalid action');
        }
        $className = '\\' . $name;
        return new $className();
    }
}
