<?php
class MemcachedCache implements CacheInterface {

	protected $path;
	protected $param;
	protected $conn;
	protected $expiration = 0;
	protected $time = false;
	protected $data = null;

	public function __construct() {
		$conn = new Memcached();
		$conn->addServer('localhost', 11211) or returnServerError('Could not connect to memcached server');
		$this->conn = $conn;
	}

	public function loadData(){
		if ($this->data) return $this->data;
		$result = $this->conn->get($this->getCacheName());
		if ($result === false) {
			return false;
		}

		$this->time = $result['time'];
		$this->data = $result['data'];
		return $result['data'];
	}

	public function saveData($datas){
		$time = time();
		$object_to_save = array(
			'data' => $datas,
			'time' => $time,
		);
		$result = $this->conn->set($this->getCacheName(), $object_to_save, $this->expiration);

		if($result === false) {
			returnServerError('Cannot write the cache to memcached server');
		}

		$this->time = $time;

		return $this;
	}

	public function getTime(){
		if ($this->time === false) {
			$this->loadData();
		}
		return $this->time;
	}

	public function purgeCache($duration){
		// Note: does not purges cache right now
		// Just sets cache expiration and leave cache purging for memcached itself
		$this->expiration = $duration;
	}

	/**
	* Set cache path
	* @return self
	*/
	public function setPath($path){
		// Note: don't know what it should do
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

	/**
	* Determines file name for store the cache
	* return string
	*/
	protected function getCacheName(){
		if(is_null($this->param)) {
			returnServerError('Call "setParameters" first!');
		}

		// Change character when making incompatible changes to prevent loading
		// errors due to incompatible file contents                               \|/
		return 'rss_bridge_cache_' . hash('md5', http_build_query($this->param) . 'A');
	}
}
