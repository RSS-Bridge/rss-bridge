<?php
/**
* Cache with file system
*/
class FileCache extends CacheAbstract{
    protected $cacheDirCreated; // boolean to avoid always chck dir cache existance

    public function loadData(){
        $this->isPrepareCache();

        $datas = unserialize(file_get_contents($this->getCacheFile()));
        $items = array();
        foreach($datas as $aData){
            $item = new \Item();
            foreach($aData as $name => $value){
                $item->$name = $value;
            }
            $items[] = $item;
        }

        return $items;
    }

    public function saveData($datas){
        $this->isPrepareCache();

        //Re-encode datas to UTF-8
        //$datas = Cache::utf8_encode_deep($datas);
        
        $writeStream = file_put_contents($this->getCacheFile(), serialize($datas));

		if(!$writeStream) {

			throw new \Exception("Cannot write the cache... Do you have the right permissions ?");

		}

        return $this;
    }

    public function getTime(){
        $this->isPrepareCache();

        $cacheFile = $this->getCacheFile();
        if( file_exists($cacheFile) ){
            return filemtime($cacheFile);
        }

        return false;
    }

    /**
    * Cache is prepared ?
    * Note : Cache name is based on request information, then cache must be prepare before use
    * @return \Exception|true
    */
    protected function isPrepareCache(){
        if( is_null($this->param) ){
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

        // FIXME : implement recursive dir creation
        if( is_null($this->cacheDirCreated) && !is_dir($cacheDir) ){
            $this->cacheDirCreated = true;

            mkdir($cacheDir,0705);
            chmod($cacheDir,0705);
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
        return hash('sha1', $stringToEncode) . '.cache';
    }
}
