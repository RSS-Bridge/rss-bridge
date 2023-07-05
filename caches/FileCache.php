<?php

class FileCache implements CacheInterface
{
    private array $config;
    protected string $scope;
    protected string $key;

    public function __construct(array $config = [])
    {
        $default = [
            'path' => null,
            'enable_purge' => true,
        ];
        $this->config = array_merge($default, $config);
        if (!$this->config['path']) {
            throw new \Exception('The FileCache needs a path value');
        }
        // Normalize with a single trailing slash
        $this->config['path'] = rtrim($this->config['path'], '/') . '/';
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function loadData()
    {
        if (!file_exists($this->getCacheFile())) {
            return null;
        }
        $data = unserialize(file_get_contents($this->getCacheFile()));
        if ($data === false) {
            // Intentionally not throwing an exception
            Logger::warning(sprintf('Failed to unserialize: %s', $this->getCacheFile()));
            return null;
        }
        return $data;
    }

    public function saveData($data): void
    {
        $writeStream = file_put_contents($this->getCacheFile(), serialize($data));
        if ($writeStream === false) {
            throw new \Exception('The cache path is not writeable. You probably want: chown www-data:www-data cache');
        }
    }

    public function getTime(): ?int
    {
        $cacheFile = $this->getCacheFile();
        clearstatcache(false, $cacheFile);
        if (file_exists($cacheFile)) {
            $time = filemtime($cacheFile);
            if ($time !== false) {
                return $time;
            }
            return null;
        }

        return null;
    }

    public function purgeCache(int $seconds): void
    {
        if (! $this->config['enable_purge']) {
            return;
        }

        $cachePath = $this->getScope();
        if (!file_exists($cachePath)) {
            return;
        }
        $cacheIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($cacheIterator as $cacheFile) {
            if (in_array($cacheFile->getBasename(), ['.', '..', '.gitkeep'])) {
                continue;
            } elseif ($cacheFile->isFile()) {
                if (filemtime($cacheFile->getPathname()) < time() - $seconds) {
                    // todo: sometimes this file doesn't exists
                    unlink($cacheFile->getPathname());
                }
            }
        }
    }

    public function setScope(string $scope): void
    {
        $this->scope = $this->config['path'] . trim($scope, " \t\n\r\0\x0B\\\/") . '/';
    }

    public function setKey(array $key): void
    {
        $this->key = json_encode($key);
    }

    private function getScope()
    {
        if (is_null($this->scope)) {
            throw new \Exception('Call "setScope" first!');
        }

        if (!is_dir($this->scope)) {
            if (mkdir($this->scope, 0755, true) !== true) {
                throw new \Exception('mkdir: Unable to create file cache folder');
            }
        }

        return $this->scope;
    }

    private function getCacheFile()
    {
        return $this->getScope() . $this->getCacheName();
    }

    private function getCacheName()
    {
        if (is_null($this->key)) {
            throw new \Exception('Call "setKey" first!');
        }

        return hash('md5', $this->key) . '.cache';
    }
}
