<?php
/**
* Cache with file system
*/
class FileCache implements CacheInterface {

	protected $param;

	public function loadData(){
		$datas = unserialize(file_get_contents($this->getCacheFile()));
		return $datas;
	}

	public function saveData($datas){
		$writeStream = file_put_contents($this->getCacheFile(), serialize($datas));

		if(!$writeStream) {
			throw new \Exception("Cannot write the cache... Do you have the right permissions ?");
		}

		return $this;
	}

	public function getTime(){
		$cacheFile = $this->getCacheFile();
		if(file_exists($cacheFile)){
			return filemtime($cacheFile);
		}

		return false;
	}

	public function purgeCache(){
		$cacheTimeLimit = time() - 86400; // 86400 -> 24h
		$cachePath = $this->getCachePath();
		if(file_exists($cachePath)){
			$cacheIterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($cachePath),
			RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach($cacheIterator as $cacheFile){
				if(in_array($cacheFile->getBasename(), array('.', '..')))
					continue;
				elseif($cacheFile->isFile()){
					if(filemtime($cacheFile->getPathname()) < $cacheTimeLimit)
						unlink($cacheFile->getPathname());
				}
			}
		}
	}

	/**
	* Set HTTP GET parameters
	* @return self
	*/
	public function setParameters(array $param){
		$this->param = $param;

		return $this;
	}

	/**
	* Return cache path (and create if not exist)
	* @return string Cache path
	*/
	protected function getCachePath(){
		$cacheDir = __DIR__ . '/../cache/'; // FIXME : configuration ?

		if(!is_dir($cacheDir)){
			mkdir($cacheDir, 0755, true);
			chmod($cacheDir, 0755);
		}

		return $cacheDir;
	}

	/**
	* Get the file name use for cache store
	* @return string Path to the file cache
	*/
	protected function getCacheFile(){
		return $this->getCachePath() . $this->getCacheName();
	}

	/**
	* Determines file name for store the cache
	* return string
	*/
	protected function getCacheName(){
		if(is_null($this->param)){
			throw new \Exception('Call "setParameters" first!');
		}

		return hash('sha1', http_build_query($this->param)) . '.cache';
	}
}
