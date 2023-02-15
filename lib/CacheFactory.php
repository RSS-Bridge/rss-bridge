<?php

declare(strict_types=1);

class CacheFactory
{
    public function create(string $name = null): CacheInterface
    {
        $name ??= Configuration::getConfig('cache', 'type');
        if (!$name) {
            throw new \Exception('No cache type configured');
        }
        $cacheNames = [];
        foreach (scandir(PATH_LIB_CACHES) as $file) {
            if (preg_match('/^([^.]+)Cache\.php$/U', $file, $m)) {
                $cacheNames[] = $m[1];
            }
        }
        // Trim trailing '.php' if exists
        if (preg_match('/(.+)(?:\.php)/', $name, $matches)) {
            $name = $matches[1];
        }
        // Trim trailing 'Cache' if exists
        if (preg_match('/(.+)(?:Cache)$/i', $name, $matches)) {
            $name = $matches[1];
        }
        if (in_array(strtolower($name), array_map('strtolower', $cacheNames))) {
            $index = array_search(strtolower($name), array_map('strtolower', $cacheNames));
            $name = $cacheNames[$index];
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid cache name: "%s"', $name));
        }
        if (! preg_match('/^[A-Z][a-zA-Z0-9-]*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Invalid cache name: "%s"', $name));
        }
        $className = $name . 'Cache';
        if (!file_exists(PATH_LIB_CACHES . $className . '.php')) {
            throw new \Exception('Unable to find the cache file');
        }
        return new $className();
    }
}
