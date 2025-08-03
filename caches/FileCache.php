<?php

declare(strict_types=1);

class FileCache implements CacheInterface
{
    private Logger $logger;
    private array $config;

    public function __construct(
        Logger $logger,
        array $config = []
    ) {
        $this->logger = $logger;
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
        $cacheFile = $this->createCacheFile($key);
        if (!file_exists($cacheFile)) {
            return $default;
        }
        $data = file_get_contents($cacheFile);
        $item = unserialize($data);
        if ($item === false) {
            $this->logger->warning(sprintf('Failed to unserialize: %s', $cacheFile));
            $this->delete($key);
            return $default;
        }
        $expiration = $item['expiration'] ?? time();
        if ($expiration === 0 || $expiration > time()) {
            return $item['value'];
        }
        $this->delete($key);
        return $default;
    }

    public function set($key, $value, ?int $ttl = null): void
    {
        $item = [
            'key'           => $key,
            'expiration'    => $ttl === null ? 0 : time() + $ttl,
            'value'         => $value,
        ];
        $cacheFile = $this->createCacheFile($key);
        $bytes = file_put_contents($cacheFile, serialize($item));

        // TODO: Consider tightening the permissions of the created file.
        // It usually allow others to read, depending on umask

        if ($bytes === false) {
            // Typically means no disk space remaining
            $this->logger->warning(sprintf('Failed to write to: %s', $cacheFile));
        }
    }

    public function delete(string $key): void
    {
        unlink($this->createCacheFile($key));
    }

    public function clear(): void
    {
        foreach (scandir($this->config['path']) as $filename) {
            $cacheFile = $this->config['path'] . $filename;
            $excluded = ['.' => true, '..' => true, '.gitkeep' => true];
            if (isset($excluded[$filename]) || !is_file($cacheFile)) {
                continue;
            }
            unlink($cacheFile);
        }
    }

    public function prune(): void
    {
        if (! $this->config['enable_purge']) {
            return;
        }
        foreach (scandir($this->config['path']) as $filename) {
            $cacheFile = $this->config['path'] . $filename;
            $excluded = ['.' => true, '..' => true, '.gitkeep' => true];
            if (isset($excluded[$filename]) || !is_file($cacheFile)) {
                continue;
            }
            $data = file_get_contents($cacheFile);
            $item = unserialize($data);
            if ($item === false) {
                unlink($cacheFile);
                continue;
            }
            $expiration = $item['expiration'] ?? time();
            if ($expiration === 0 || $expiration > time()) {
                // Cached forever, or not expired yet
                continue;
            }
            // Expired, so delete file
            unlink($cacheFile);
        }
    }

    private function createCacheFile(string $key): string
    {
        return $this->config['path'] . hash('md5', $key) . '.cache';
    }

    public function getConfig()
    {
        return $this->config;
    }
}
