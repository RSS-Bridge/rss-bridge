<?php
/**
* Cache with file system
*/
class FileCache implements CacheInterface {

	protected $path;
	protected $param;

	public function loadData(){
		if(file_exists($this->getCacheFile())) {
			return unserialize(file_get_contents($this->getCacheFile()));
		}
	}

	public function saveData($datas){
		// Notice: We use plain serialize() here to reduce memory footprint on
		// large input data.
		$writeStream = file_put_contents($this->getCacheFile(), serialize($datas));

		if($writeStream === false) {
			throw new \Exception('Cannot write the cache... Do you have the right permissions ?');
		}

		return $this;
	}

	public function getTime(){
		$cacheFile = $this->getCacheFile();
		clearstatcache(false, $cacheFile);
		if(file_exists($cacheFile)) {
			return filemtime($cacheFile);
		}

		return false;
	}

	public function purgeCache($duration){
		$cachePath = $this->getPath();
		if(file_exists($cachePath)) {
			$cacheIterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($cachePath),
			RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach($cacheIterator as $cacheFile) {
				if(in_array($cacheFile->getBasename(), array('.', '..', '.gitkeep')))
					continue;
				elseif($cacheFile->isFile()) {
					if(filemtime($cacheFile->getPathname()) < time() - $duration)
						unlink($cacheFile->getPathname());
				}
			}
		}
	}

	/**
	* Set cache path
	* @return self
	*/
	public function setPath($path){
		if(is_null($path) || !is_string($path)) {
			throw new \Exception('The given path is invalid!');
		}

		$this->path = $path;

		// Make sure path ends with '/' or '\'
		$lastchar = substr($this->path, -1, 1);
		if($lastchar !== '/' && $lastchar !== '\\')
			$this->path .= '/';

		if(!is_dir($this->path))
			mkdir($this->path, 0755, true);

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
	* Return cache path (and create if not exist)
	* @return string Cache path
	*/
	protected function getPath(){
		if(is_null($this->path)) {
			throw new \Exception('Call "setPath" first!');
		}

		return $this->path;
	}

	/**
	* Get the file name use for cache store
	* @return string Path to the file cache
	*/
	protected function getCacheFile(){
		return $this->getPath() . $this->getCacheName();
	}

	/**
	* Determines file name for store the cache
	* return string
	*/
	protected function getCacheName(){
		if(is_null($this->param)) {
			throw new \Exception('Call "setParameters" first!');
		}

		// Change character when making incompatible changes to prevent loading
		// errors due to incompatible file contents         \|/
		return hash('md5', http_build_query($this->param) . 'A') . '.cache';
	}
}
