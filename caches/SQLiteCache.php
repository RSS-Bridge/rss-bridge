<?php

/**
 * Cache based on SQLite 3 <https://www.sqlite.org>
 */
class SQLiteCache implements CacheInterface
{
    protected $scope;
    protected $key;

    private $db = null;

    public function __construct()
    {
        if (!extension_loaded('sqlite3')) {
            throw new \Exception('"sqlite3" extension not loaded. Please check "php.ini"');
        }

        if (!is_writable(PATH_CACHE)) {
            throw new \Exception('The cache folder is not writable');
        }

        $section = 'SQLiteCache';
        $file = Configuration::getConfig($section, 'file');
        if (!$file) {
            throw new \Exception(sprintf('Configuration for %s missing.', $section));
        }

        if (dirname($file) == '.') {
            $file = PATH_CACHE . $file;
        } elseif (!is_dir(dirname($file))) {
            throw new \Exception(sprintf('Invalid configuration for %s', $section));
        }

        if (!is_file($file)) {
            // The instantiation creates the file
            $this->db = new \SQLite3($file);
            $this->db->enableExceptions(true);
            $this->db->exec("CREATE TABLE storage ('key' BLOB PRIMARY KEY, 'value' BLOB, 'updated' INTEGER)");
        } else {
            $this->db = new \SQLite3($file);
            $this->db->enableExceptions(true);
        }
        $this->db->busyTimeout(5000);
    }

    public function loadData()
    {
        $Qselect = $this->db->prepare('SELECT value FROM storage WHERE key = :key');
        $Qselect->bindValue(':key', $this->getCacheKey());
        $result = $Qselect->execute();
        if ($result instanceof \SQLite3Result) {
            $data = $result->fetchArray(\SQLITE3_ASSOC);
            if (isset($data['value'])) {
                return unserialize($data['value']);
            }
        }

        return null;
    }

    public function saveData($data)
    {
        $Qupdate = $this->db->prepare('INSERT OR REPLACE INTO storage (key, value, updated) VALUES (:key, :value, :updated)');
        $Qupdate->bindValue(':key', $this->getCacheKey());
        $Qupdate->bindValue(':value', serialize($data));
        $Qupdate->bindValue(':updated', time());
        $Qupdate->execute();

        return $this;
    }

    public function getTime()
    {
        $Qselect = $this->db->prepare('SELECT updated FROM storage WHERE key = :key');
        $Qselect->bindValue(':key', $this->getCacheKey());
        $result = $Qselect->execute();
        if ($result instanceof \SQLite3Result) {
            $data = $result->fetchArray(SQLITE3_ASSOC);
            if (isset($data['updated'])) {
                return $data['updated'];
            }
        }

        return null;
    }

    public function purgeCache($seconds)
    {
        $Qdelete = $this->db->prepare('DELETE FROM storage WHERE updated < :expired');
        $Qdelete->bindValue(':expired', time() - $seconds);
        $Qdelete->execute();
    }

    public function setScope($scope)
    {
        if (is_null($scope) || !is_string($scope)) {
            throw new \Exception('The given scope is invalid!');
        }

        $this->scope = $scope;
        return $this;
    }

    public function setKey($key)
    {
        if (!empty($key) && is_array($key)) {
            $key = array_map('strtolower', $key);
        }
        $key = json_encode($key);

        if (!is_string($key)) {
            throw new \Exception('The given key is invalid!');
        }

        $this->key = $key;
        return $this;
    }

    private function getCacheKey()
    {
        if (is_null($this->key)) {
            throw new \Exception('Call "setKey" first!');
        }

        return hash('sha1', $this->scope . $this->key, true);
    }
}
