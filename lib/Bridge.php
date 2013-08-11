<?php
/**
* All bridge logic
* Note : adapter are store in other place
*/

interface BridgeInterface{
    public function collectData(array $param);
    public function getName();
    public function getURI();
    public function getCacheDuration();
}

abstract class BridgeAbstract implements BridgeInterface{
    protected $cache;
    protected $items = array();

    /**
    * Launch probative exception
    */
    protected function returnError($message, $code){
        throw new \HttpException($message, $code);
    }

    /**
    * Return datas store in the bridge
    * @return mixed
    */
    public function getDatas(){
        return $this->items;
    }

    /**
    * Defined datas with parameters depending choose bridge
    * Note : you can defined a cache before with "setCache"
    * @param array $param $_REQUEST, $_GET, $_POST, or array with bridge expected paramters
    */
    public function setDatas(array $param){
        if( !is_null($this->cache) ){
            $this->cache->prepare($param);
            $time = $this->cache->getTime();
        }
        else{
            $time = false; // No cache ? No time !
        }

        if( $time !== false && ( time() - $this->getCacheDuration() < $time ) ){ // Cache file has not expired. Serve it.
            $this->items = $this->cache->loadData();
        }
        else{
            $this->collectData($param);

            if( !is_null($this->cache) ){ // Cache defined ? We go to refresh is memory :D
                $this->cache->saveData($this->getDatas());
            }
        }
    }

    /**
    * Define default duraction for cache
    */
    public function getCacheDuration(){
        return 3600;
    }

    /**
    * Defined cache object to use
    */
    public function setCache(\CacheAbstract $cache){
        $this->cache = $cache;

        return $this;
    }
}

class Bridge{

    static protected $dirBridge;

    public function __construct(){
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

    /**
    * Create a new bridge object
    * @param string $nameBridge Defined bridge name you want use
    * @return Bridge object dedicated
    */
    static public function create($nameBridge){
        if( !static::isValidNameBridge($nameBridge) ){
            throw new \InvalidArgumentException('Name bridge must be at least one uppercase follow or not by alphanumeric or dash characters.');
        }

        $pathBridge = self::getDir() . $nameBridge . '.php';

        if( !file_exists($pathBridge) ){
            throw new \Exception('The bridge you looking for does not exist.');
        }

        require_once $pathBridge;

        return new $nameBridge();
    }

    static public function setDir($dirBridge){
        if( !is_string($dirBridge) ){
            throw new \InvalidArgumentException('Dir bridge must be a string.');
        }

        if( !file_exists($dirBridge) ){
            throw new \Exception('Dir bridge does not exist.');
        }

        self::$dirBridge = $dirBridge;
    }

    static public function getDir(){
        $dirBridge = self::$dirBridge;

        if( is_null($dirBridge) ){
            throw new \LogicException(__CLASS__ . ' class need to know bridge path !');
        }

        return $dirBridge;
    }

    static public function isValidNameBridge($nameBridge){
        return preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameBridge);
    }

    /**
    * Read bridge dir and catch informations about each bridge depending annotation
    * @return array Informations about each bridge
    */
    static public function searchInformation(){
        $pathDirBridge = self::getDir();

        $listBridge = array();

        $searchCommonPattern = array('description', 'name');

        $dirFiles = scandir($pathDirBridge);
        if( $dirFiles !== false ){
            foreach( $dirFiles as $fileName ){
                if( preg_match('@([^.]+)\.php@U', $fileName, $out) ){ // Is PHP file ?
                    $infos = array(); // Information about the bridge
                    $resParse = token_get_all(file_get_contents($pathDirBridge . $fileName)); // Parse PHP file
                    foreach($resParse as $v){
                        if( is_array($v) && $v[0] == T_DOC_COMMENT ){ // Lexer node is COMMENT ?
                            $commentary = $v[1];
                            foreach( $searchCommonPattern as $name){ // Catch information with common pattern
                                preg_match('#@' . preg_quote($name, '#') . '\s+(.+)#', $commentary, $outComment);
                                if( isset($outComment[1]) ){
                                    $infos[$name] = $outComment[1];
                                }
                            }

                            preg_match_all('#@use(?<num>[1-9][0-9]*)\s?\((?<args>.+)\)(?:\r|\n)#', $commentary, $outComment); // Catch specific information about "use".
                            if( isset($outComment['args']) && is_array($outComment['args']) ){
                                $infos['use'] = array();
                                foreach($outComment['args'] as $num => $args){ // Each use
                                    preg_match_all('#(?<name>[a-z]+)="(?<value>.*)"(?:,|$)#U', $args, $outArg); // Catch arguments for current use
                                    if( isset($outArg['name']) ){
                                        $usePos = $outComment['num'][$num]; // Current use name
                                        if( !isset($infos['use'][$usePos]) ){ // Not information actually for this "use" ?
                                            $infos['use'][$usePos] = array();
                                        }

                                        foreach($outArg['name'] as $numArg => $name){ // Each arguments
                                            $infos['use'][$usePos][$name] = $outArg['value'][$numArg];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if( isset($infos['name']) ){ // If informations containt at least a name
                        $listBridge[$out[1]] = $infos;
                    }
                }
            }
        }

        return $listBridge;
    }
}