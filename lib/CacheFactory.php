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

class CacheFactory
{
    private function __construct()
    {
    }

    public static function create(string $name = null): CacheInterface
    {
        $name ??= Configuration::getConfig('cache', 'type');
        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }
        // Trim trailing 'Cache' if exists
        if (preg_match('/(.+)(?:Cache)$/i', $name, $matches)) {
            $name = $matches[1];
        }
        $caches = [
            'file'      => FileCache::class,
            'memcached' => MemcachedCache::class,
            'sqlite'    => SQLiteCache::class,
        ];
        $name = mb_strtolower($name);
        if (! isset($caches[$name])) {
            throw new \InvalidArgumentException('Cache name invalid!');
        }
        return new $caches[$name]();
    }
}
