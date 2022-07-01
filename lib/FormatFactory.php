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

class FormatFactory
{
    private $folder;
    private $formatNames;

    public function __construct(string $folder = PATH_LIB_FORMATS)
    {
        $this->folder = $folder;

        // create format names
        foreach (scandir($this->folder) as $file) {
            if (preg_match('/^([^.]+)Format\.php$/U', $file, $m)) {
                $this->formatNames[] = $m[1];
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @param string $name The name of the format e.g. "Atom", "Mrss" or "Json"
     */
    public function create(string $name): FormatInterface
    {
        if (! preg_match('/^[a-zA-Z0-9-]*$/', $name)) {
            throw new \InvalidArgumentException('Format name invalid!');
        }
        $name = $this->sanitizeFormatName($name);
        if ($name === null) {
            throw new \InvalidArgumentException('Unknown format given!');
        }
        $className = '\\' . $name . 'Format';
        return new $className();
    }

    public function getFormatNames(): array
    {
        return $this->formatNames;
    }

    protected function sanitizeFormatName(string $name)
    {
        $name = ucfirst(strtolower($name));

        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }

        // Trim trailing 'Format' if exists
        if (preg_match('/(.+)(?:Format)/i', $name, $matches)) {
            $name = $matches[1];
        }
        if (in_array($name, $this->formatNames)) {
            return $name;
        }
        return null;
    }
}
