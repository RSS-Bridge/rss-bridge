<?php
/**
* All cache logic
* Note : adapter are store in other place
*/

interface CacheInterface{
    public function loadData();
    public function saveData($datas);
    public function getTime();
}

abstract class CacheAbstract implements CacheInterface{
    protected $param;

    public function prepare(array $param){
        $this->param = $param;

        return $this;
    }
}

class Cache{

    static protected $dirCache;

    public function __construct(){
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

    static public function create($nameCache){
        if( !static::isValidNameCache($nameCache) ){
            throw new \InvalidArgumentException('Name cache must be at least one uppercase follow or not by alphanumeric or dash characters.');
        }

        $pathCache = self::getDir() . $nameCache . '.php';

        if( !file_exists($pathCache) ){
            throw new \Exception('The cache you looking for does not exist.');
        }

        require_once $pathCache;

        return new $nameCache();
    }

    static public function setDir($dirCache){
        if( !is_string($dirCache) ){
            throw new \InvalidArgumentException('Dir cache must be a string.');
        }

        if( !file_exists($dirCache) ){
            throw new \Exception('Dir cache does not exist.');
        }

        self::$dirCache = $dirCache;
    }

    static public function getDir(){
        $dirCache = self::$dirCache;

        if( is_null($dirCache) ){
            throw new \LogicException(__CLASS__ . ' class need to know cache path !');
        }

        return $dirCache;
    }

    static public function isValidNameCache($nameCache){
        return preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameCache);
    }


    static public function utf8_encode_deep(&$input) {
        if (is_string($input)) {
            $input = utf8_encode($input);
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                Cache::utf8_encode_deep($value);
            }

            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));

            foreach ($vars as $var) {
                Cache::utf8_encode_deep($input->$var);
            }
        }
    }

    
	static public function purge() {
		$cacheTimeLimit = time() - 60*60*24 ;
		$cachePath = 'cache';
		if(file_exists($cachePath)) {
		   $cacheIterator = new RecursiveIteratorIterator(
			 new RecursiveDirectoryIterator($cachePath),
			 RecursiveIteratorIterator::CHILD_FIRST
		   );
		   foreach ($cacheIterator as $cacheFile) {
			  if (in_array($cacheFile->getBasename(), array('.', '..')))
				 continue;
			  elseif ($cacheFile->isFile()) {
				 if( filemtime($cacheFile->getPathname()) < $cacheTimeLimit )
				    unlink( $cacheFile->getPathname() );
				 }
		   }
		}
	}

}
