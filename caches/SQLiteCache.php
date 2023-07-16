<?php

class SQLiteCache implements CacheInterface
{
    private \SQLite3 $db;
    private string $scope;
    private string $key;
    private array $config;

    public function __construct(array $config)
    {
        $default = [
            'file'          => null,
            'timeout'       => 5000,
            'enable_purge'  => true,
        ];
        $config = array_merge($default, $config);
        $this->config = $config;

        if (!$config['file']) {
            throw new \Exception('sqlite cache needs a file');
        }

        if (is_file($config['file'])) {
            $this->db = new \SQLite3($config['file']);
            $this->db->enableExceptions(true);
        } else {
            // Create the file and create sql schema
            $this->db = new \SQLite3($config['file']);
            $this->db->enableExceptions(true);
            $this->db->exec("CREATE TABLE storage ('key' BLOB PRIMARY KEY, 'value' BLOB, 'updated' INTEGER)");
            $this->db->exec('CREATE INDEX idx_storage_updated ON storage (updated)');
        }
        $this->db->busyTimeout($config['timeout']);
    }

    public function loadData()
    {
        $stmt = $this->db->prepare('SELECT value FROM storage WHERE key = :key');
        $stmt->bindValue(':key', $this->getCacheKey());
        $result = $stmt->execute();
        if ($result) {
            $row = $result->fetchArray(\SQLITE3_ASSOC);
            if ($row !== false) {
                $blob = $row['value'];
                $data = unserialize($blob);
                if ($data !== false) {
                    return $data;
                }
                Logger::error(sprintf("Failed to unserialize: '%s'", mb_substr($blob, 0, 100)));
            }
        }
        return null;
    }

    public function saveData($data): void
    {
        $blob = serialize($data);

        $stmt = $this->db->prepare('INSERT OR REPLACE INTO storage (key, value, updated) VALUES (:key, :value, :updated)');
        $stmt->bindValue(':key', $this->getCacheKey());
        $stmt->bindValue(':value', $blob);
        $stmt->bindValue(':updated', time());
        $stmt->execute();
    }

    public function getTime(): ?int
    {
        $stmt = $this->db->prepare('SELECT updated FROM storage WHERE key = :key');
        $stmt->bindValue(':key', $this->getCacheKey());
        $result = $stmt->execute();
        if ($result) {
            $row = $result->fetchArray(\SQLITE3_ASSOC);
            if ($row !== false) {
                return $row['updated'];
            }
        }
        return null;
    }

    public function purgeCache(int $seconds): void
    {
        if (!$this->config['enable_purge']) {
            return;
        }
        $stmt = $this->db->prepare('DELETE FROM storage WHERE updated < :expired');
        $stmt->bindValue(':expired', time() - $seconds);
        $stmt->execute();
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
        return hash('sha1', $this->scope . $this->key, true);
    }
}
