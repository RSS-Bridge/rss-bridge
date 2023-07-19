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
        }
        $this->db->busyTimeout($config['timeout']);
    }

    public function loadData(int $timeout = 86400)
    {
        $stmt = $this->db->prepare('SELECT value, updated FROM storage WHERE key = :key');
        $stmt->bindValue(':key', $this->getCacheKey());
        $result = $stmt->execute();
        if (!$result) {
            return null;
        }
        $row = $result->fetchArray(\SQLITE3_ASSOC);
        if ($row === false) {
            return null;
        }
        $value = $row['value'];
        $modificationTime = $row['updated'];
        if (time() - $timeout < $modificationTime) {
            $data = unserialize($value);
            if ($data === false) {
                Logger::error(sprintf("Failed to unserialize: '%s'", mb_substr($value, 0, 100)));
                return null;
            }
            return $data;
        }
        // It's a good idea to delete expired cache items.
        // However I'm seeing lots of  SQLITE_BUSY errors so commented out for now
        // $stmt = $this->db->prepare('DELETE FROM storage WHERE key = :key');
        // $stmt->bindValue(':key', $this->getCacheKey());
        // $stmt->execute();
        return null;
    }

    public function saveData($data): void
    {
        $blob = serialize($data);

        $stmt = $this->db->prepare('INSERT OR REPLACE INTO storage (key, value, updated) VALUES (:key, :value, :updated)');
        $stmt->bindValue(':key', $this->getCacheKey());
        $stmt->bindValue(':value', $blob, \SQLITE3_BLOB);
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

    public function purgeCache(int $timeout = 86400): void
    {
        if (!$this->config['enable_purge']) {
            return;
        }
        $stmt = $this->db->prepare('DELETE FROM storage WHERE updated < :expired');
        $stmt->bindValue(':expired', time() - $timeout);
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
