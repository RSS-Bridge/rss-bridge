<?php
/**
 * Cache based on SQLite 3 <https://www.sqlite.org>
 */
class SQLiteCache implements CacheInterface {
	protected $path;
	protected $param;

	private $db = null;

	public function __construct() {
		if (!extension_loaded('sqlite3'))
			die('"sqlite3" extension not loaded. Please check "php.ini"');

		$file = PATH_CACHE . 'cache.sqlite';

		if (!is_file($file)) {
			$this->db = new SQLite3($file);
			$this->db->enableExceptions(true);
			$this->db->exec("CREATE TABLE storage ('key' BLOB PRIMARY KEY, 'value' BLOB, 'updated' INTEGER)");
		} else {
			$this->db = new SQLite3($file);
			$this->db->enableExceptions(true);
		}
		$this->db->busyTimeout(5000);
	}

	public function loadData(){
		$Qselect = $this->db->prepare('SELECT value FROM storage WHERE key = :key');
		$Qselect->bindValue(':key', $this->getCacheKey());
		$result = $Qselect->execute();
		if ($result instanceof SQLite3Result) {
			$data = $result->fetchArray(SQLITE3_ASSOC);
			if (isset($data['value'])) {
				return unserialize($data['value']);
			}
		}

		return null;
	}

	public function saveData($datas){
		$Qupdate = $this->db->prepare('INSERT OR REPLACE INTO storage (key, value, updated) VALUES (:key, :value, :updated)');
		$Qupdate->bindValue(':key', $this->getCacheKey());
		$Qupdate->bindValue(':value', serialize($datas));
		$Qupdate->bindValue(':updated', time());
		$Qupdate->execute();

		return $this;
	}

	public function getTime(){
		$Qselect = $this->db->prepare('SELECT updated FROM storage WHERE key = :key');
		$Qselect->bindValue(':key', $this->getCacheKey());
		$result = $Qselect->execute();
		if ($result instanceof SQLite3Result) {
			$data = $result->fetchArray(SQLITE3_ASSOC);
			if (isset($data['updated'])) {
				return $data['updated'];
			}
		}

		return false;
	}

	public function purgeCache($duration){
		$Qdelete = $this->db->prepare('DELETE FROM storage WHERE updated < :expired');
		$Qdelete->bindValue(':expired', time() - $duration);
		$Qdelete->execute();
	}

	/**
	* Set cache path
	* @return self
	*/
	public function setPath($path){
		$this->path = $path;
		return $this;
	}

	/**
	* Set HTTP GET parameters
	* @return self
	*/
	public function setParameters(array $param){
		$this->param = array_map('strtolower', $param);
		return $this;
	}

	////////////////////////////////////////////////////////////////////////////

	protected function getCacheKey(){
		if(is_null($this->param)) {
			throw new \Exception('Call "setParameters" first!');
		}

		return hash('sha1', $this->path . http_build_query($this->param), true);
	}
}
