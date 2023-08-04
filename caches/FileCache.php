<?php

declare(strict_types=1);

/**
 * @link https://www.php.net/manual/en/function.clearstatcache.php
 */
class FileCache implements CacheInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $default = [
            'path'          => null,
            'enable_purge'  => true,
        ];
        $this->config = array_merge($default, $config);
        if (!$this->config['path']) {
            throw new \Exception('The FileCache needs a path value');
        }
        // Normalize with a single trailing slash
        $this->config['path'] = rtrim($this->config['path'], '/') . '/';
    }

    public function get(string $key, $default = null)
    {
        clearstatcache();
        $cacheFile = $this->createCacheFile($key);
        if (!file_exists($cacheFile)) {
            return $default;
        }
        $item = unserialize(file_get_contents($cacheFile));
        if ($item === false) {
            Logger::warning(sprintf('Failed to unserialize: %s', $cacheFile));
            // delete?
            return $default;
        }
        $expiration = $item['expiration'];
        if ($expiration === 0 || $expiration > time()) {
            return $item['value'];
        }
        // delete?
        return $default;
    }

    public function set($key, $value, int $ttl = null): void
    {
        $item = [
            'key'           => $key,
            'value'         => $value,
            'expiration'    => $ttl === null ? 0 : time() + $ttl,
        ];
        $cacheFile = $this->createCacheFile($key);
        $bytes = file_put_contents($cacheFile, serialize($item), LOCK_EX);
        if ($bytes === false) {
            // Consider just logging the error here
            throw new \Exception(sprintf('Failed to write to: %s', $cacheFile));
        }
    }

    public function delete(string $key): void
    {
        unlink($this->createCacheFile($key));
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }

    public function prune(): void
    {
        if (! $this->config['enable_purge']) {
            return;
        }

        $cachePath = $this->config['path'];
        $cacheIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($cacheIterator as $cacheFile) {
            $basename = $cacheFile->getBasename();
            $excluded = [
                '.'         => true,
                '..'        => true,
                '.gitkeep'  => true,
            ];
            if (isset($excluded[$basename])) {
                continue;
            } elseif ($cacheFile->isFile()) {
                $filepath = $cacheFile->getPathname();
                if (filemtime($filepath) < time() - $timeout) {
                    // todo: sometimes this file doesn't exists
                    unlink($filepath);
                }
            }
        }
    }

    private function createCacheFile(string $key): string
    {
        return $this->config['path'] . hash('md5', $key) . '.cache';
    }
}
