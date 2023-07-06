<?php

class MemcachedCache implements CacheInterface
{
    private string $scope;
    private string $key;
    private $conn;
    private $expiration = 0;
    private $time = null;
    private $data = null;

    public function __construct()
    {
        if (!extension_loaded('memcached')) {
            throw new \Exception('"memcached" extension not loaded. Please check "php.ini"');
        }

        $section = 'MemcachedCache';
        $host = Configuration::getConfig($section, 'host');
        $port = Configuration::getConfig($section, 'port');

        if (empty($host) && empty($port)) {
            throw new \Exception('Configuration for ' . $section . ' missing.');
        }
        if (empty($host)) {
            throw new \Exception('"host" param is not set for ' . $section);
        }
        if (empty($port)) {
            throw new \Exception('"port" param is not set for ' . $section);
        }
        if (!ctype_digit($port)) {
            throw new \Exception('"port" param is invalid for ' . $section);
        }

        $port = intval($port);

        if ($port < 1 || $port > 65535) {
            throw new \Exception('"port" param is invalid for ' . $section);
        }

        $conn = new \Memcached();
        $conn->addServer($host, $port) or returnServerError('Could not connect to memcached server');
        $this->conn = $conn;
    }

    public function loadData()
    {
        if ($this->data) {
            return $this->data;
        }
        $result = $this->conn->get($this->getCacheKey());
        if ($result === false) {
            return null;
        }

        $this->time = $result['time'];
        $this->data = $result['data'];
        return $result['data'];
    }

    public function saveData($data): void
    {
        $time = time();
        $object_to_save = [
            'data' => $data,
            'time' => $time,
        ];
        $result = $this->conn->set($this->getCacheKey(), $object_to_save, $this->expiration);

        if ($result === false) {
            throw new \Exception('Cannot write the cache to memcached server');
        }

        $this->time = $time;
    }

    public function getTime(): ?int
    {
        if ($this->time === null) {
            $this->loadData();
        }
        return $this->time;
    }

    public function purgeCache(int $seconds): void
    {
        // Note: does not purges cache right now
        // Just sets cache expiration and leave cache purging for memcached itself
        $this->expiration = $seconds;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function setKey(array $key): void
    {
        $this->key = json_encode($key);
    }

    private function getCacheKey()
    {
        if (is_null($this->key)) {
            throw new \Exception('Call "setKey" first!');
        }

        return 'rss_bridge_cache_' . hash('md5', $this->scope . $this->key . 'A');
    }
}
