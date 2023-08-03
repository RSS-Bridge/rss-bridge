<?php

class MemcachedCache implements CacheInterface
{
    private $conn;

    public function __construct(string $host, int $port)
    {
        $conn = new \Memcached();
        // This call does not actually connect to server yet
        if (!$conn->addServer($host, $port)) {
            throw new \Exception('Unable to add memcached server');
        }
        $this->conn = $conn;
    }

    public function get($key, $default = null)
    {
        $cacheKey = 'rss_bridge_cache_' . hash('md5', json_encode($key) . 'A');
        $value = $this->conn->get($cacheKey);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    public function set($key, $value, $ttl = null): void
    {
        $key = json_encode($key);
        $expiration = $ttl === null ? 0 : time() + $ttl;
        $cacheKey = 'rss_bridge_cache_' . hash('md5', $key . 'A');
        $result = $this->conn->set($cacheKey, $value, $expiration);
        if ($result === false) {
            Logger::warning('Failed to store an item in memcached', [
                'code'          => $this->conn->getLastErrorCode(),
                'message'       => $this->conn->getLastErrorMessage(),
                'number'        => $this->conn->getLastErrorErrno(),
                'key'           => $key,
            ]);
            // Intentionally not throwing an exception
        }
    }

    public function purgeCache(int $timeout = 86400): void
    {
        //$this->conn->flush();
    }

    public function clear(): void
    {
        $this->conn->flush();
    }
}
