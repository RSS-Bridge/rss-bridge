<?php
/**
* Cache with file system
*/
class FileCache implements CacheInterface {

	protected $param;

	public function loadData(){
		$this->isPrepareCache();
		$datas = unserialize(file_get_contents($this->getCacheFile()));
		return $datas;
	}

	public function saveData($datas){
		$this->isPrepareCache();

		$writeStream = file_put_contents($this->getCacheFile(), serialize($datas));

		if(!$writeStream) {
			throw new \Exception("Cannot write the cache... Do you have the right permissions ?");
		}

		return $this;
	}

	public function getTime(){
		$this->isPrepareCache();

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

	public function prepare(array $param){
		$this->param = $param;

		return $this;
	}

	/**
	* Cache is prepared ?
	* Note : Cache name is based on request information, then cache must be prepare before use
	* @return \Exception|true
	*/
	protected function isPrepareCache(){
		if(is_null($this->param)){
			throw new \Exception('Please feed "prepare" method before try to load');
		}

		return true;
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
		$this->isPrepareCache();

		$stringToEncode = $_SERVER['REQUEST_URI'] . http_build_query($this->param);
		$stringToEncode = preg_replace('/(\?|&)format=[^&]*/i', '$1', $stringToEncode);
		return hash('sha1', $stringToEncode) . '.cache';
	}
}
