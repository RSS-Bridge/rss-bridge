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

    public function loadData(int $timeout = 86400)
    {
        $value = $this->conn->get($this->getCacheKey());
        if ($value === false) {
            return null;
        }
        if (time() - $timeout < $value['time']) {
            $this->time = $value['time'];
            $this->data = $value['data'];
            return $value['data'];
        }
        return null;
    }

    public function saveData($data): void
    {
        $value = [
            'data' => $data,
            'time' => time(),
        ];
        $result = $this->conn->set($this->getCacheKey(), $value, $this->expiration);
        if ($result === false) {
            throw new \Exception('Cannot write the cache to memcached server');
        }
        $this->time = $value['time'];
    }

    public function getTime(): ?int
    {
        if ($this->time === null) {
            $this->loadData();
        }
        return $this->time;
    }

    public function purgeCache(int $timeout = 86400): void
    {
        $this->conn->flush();
        // Note: does not purges cache right now
        // Just sets cache expiration and leave cache purging for memcached itself
        $this->expiration = $timeout;
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
