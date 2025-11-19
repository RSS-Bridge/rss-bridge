<?php

declare(strict_types=1);

class MemcachedCache implements CacheInterface
{
    private Logger $logger;
    private \Memcached $conn;

    public function __construct(
        Logger $logger,
        string $host,
        int $port
    ) {
        $this->logger = $logger;
        $this->conn = new \Memcached();
        // This call does not actually connect to server yet
        if (!$this->conn->addServer($host, $port)) {
            throw new \Exception('Unable to add memcached server');
        }
    }

    public function get(string $key, $default = null)
    {
        $value = $this->conn->get($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    public function set(string $key, $value, $ttl = null): void
    {
        if ($ttl === 0) {
            return; // TTL is 0, do nothing
        }

        $expiration = $ttl === null ? 0 : time() + $ttl; // if ttl not provided, store forever
        $result = $this->conn->set($key, $value, $expiration);
        if ($result === false) {
            $this->logger->warning('Failed to store an item in memcached', [
                'key'           => $key,
                'resultCode'    => $this->conn->getResultCode(),
                'code'          => $this->conn->getLastErrorCode(),
                'message'       => $this->conn->getLastErrorMessage(),
                'number'        => $this->conn->getLastErrorErrno(),
            ]);
            // Intentionally not throwing an exception
        }
    }

    public function delete(string $key): void
    {
        $this->conn->delete($key);
    }

    public function clear(): void
    {
        $this->conn->flush();
    }

    public function prune(): void
    {
        // memcached manages pruning on its own
    }
}
