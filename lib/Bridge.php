<?php
class Bridge {

    static protected $dirBridge;

    public function __construct(){
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

    /**
    * Checks if a bridge is an instantiable bridge.
    * @param string $nameBridge name of the bridge that you want to use
    * @return true if it is an instantiable bridge, false otherwise.
    */
    static public function isInstantiable($nameBridge){
        $re = new ReflectionClass($nameBridge);
        return $re->IsInstantiable();
    }

    /**
    * Create a new bridge object
    * @param string $nameBridge Defined bridge name you want use
    * @return Bridge object dedicated
    */
    static public function create($nameBridge){
        if(!preg_match('@^[A-Z][a-zA-Z0-9-]*$@', $nameBridge)){
            $message = <<<EOD
'nameBridge' must start with one uppercase character followed or not by
alphanumeric or dash characters!
EOD;
            throw new \InvalidArgumentException($message);
        }

        $nameBridge = $nameBridge . 'Bridge';
        $pathBridge = self::getDir() . $nameBridge . '.php';

        if(!file_exists($pathBridge)){
            throw new \Exception('The bridge you looking for does not exist. It should be at path ' . $pathBridge);
        }

        require_once $pathBridge;

        if(Bridge::isInstantiable($nameBridge)){
            return new $nameBridge();
        } else {
            return false;
        }
    }

    static public function setDir($dirBridge){
        if(!is_string($dirBridge)){
            throw new \InvalidArgumentException('Dir bridge must be a string.');
        }

        if(!file_exists($dirBridge)){
            throw new \Exception('Dir bridge does not exist.');
        }

        self::$dirBridge = $dirBridge;
    }

    static public function getDir(){
        $dirBridge = self::$dirBridge;

        if(is_null($dirBridge)){
            throw new \LogicException(__CLASS__ . ' class need to know bridge path !');
        }

        return $dirBridge;
    }

    /**
    * Lists the available bridges.
    * @return array List of the bridges
    */
    static public function listBridges(){
        $pathDirBridge = self::getDir();
        $listBridge = array();
        $dirFiles = scandir($pathDirBridge);

        if($dirFiles !== false){
            foreach($dirFiles as $fileName){
                if(preg_match('@^([^.]+)Bridge\.php$@U', $fileName, $out)){
                    $listBridge[] = $out[1];
                }
            }
        }

        return $listBridge;
    }

    static public function isWhitelisted($whitelist, $name){
        if(in_array($name, $whitelist)
          or in_array($name . '.php', $whitelist)
          or in_array($name . 'Bridge', $whitelist) // DEPRECATED
          or in_array($name . 'Bridge.php', $whitelist) // DEPRECATED
          or count($whitelist) === 1 and trim($whitelist[0]) === '*'){
            return true;
        } else {
            return false;
        }
    }
}

interface BridgeInterface {
    public function collectData();
    public function getCacheDuration();
    public function getName();
    public function getURI();
}

abstract class BridgeAbstract implements BridgeInterface {

    protected $cache;
    protected $items = array();

    public $name = 'Unnamed bridge';
    public $uri = '';
    public $description = 'No description provided';
    public $maintainer = 'No maintainer';
    public $useProxy = true;
    public $parameters = array();
    protected $queriedContext='';

    protected function returnError($message, $code){
        throw new \HttpException($message, $code);
    }

    protected function returnClientError($message){
        $this->returnError($message, 400);
    }

    protected function returnServerError($message){
        $this->returnError($message, 500);
    }

    /**
    * Return items stored in the bridge
    * @return mixed
    */
    public function getDatas(){
        return $this->items;
    }

    protected function validateData(&$data){
        $validated=true;
        foreach($data as $name=>$value){
            $registered=false;
            foreach($this->parameters as $context=>$set){
                if(array_key_exists($name,$set)){
                    $registered=true;
                    if(!isset($set[$name]['type'])){
                        $set[$name]['type']='text';
                    }
                    switch($set[$name]['type']){
                    case 'number':
                        $data[$name]=filter_var($value,FILTER_VALIDATE_INT);
                        if($data[$name]===false && !empty($value)){
                            $validated=false;
                        }
                        break;
                    case 'checkbox':
                        $data[$name]=filter_var($value,FILTER_VALIDATE_BOOLEAN,
                            FILTER_NULL_ON_FAILURE);
                        if(is_null($data[$name])){
                            $validated=false;
                        }
                        break;
                    case 'list':
                        $data[$name]=filter_var($value);
                        if(!in_array($value,$set[$name]['values'])){
                            foreach($set[$name]['values'] as $subName=>$subValue){
                                if(is_array($subValue) &&
                                    in_array($value,$subValue)){
                                    $data[$name]=filter_var($value);
                                    break 2;
                                }
                            }
                            $validated=false;
                            $data[$name]=null;
                        }
                        break;
                    default:
                    case 'text':
                        if(isset($set[$name]['pattern'])){
                            $data[$name]=filter_var($value,FILTER_VALIDATE_REGEXP,
                                array('options'=>array(
                                    'regexp'=>'/^'.$set[$name]['pattern'].'$/'
                                ))
                            );
                        }else{
                            $data[$name]=filter_var($value);
                        }
                        if($data[$name]===false && !empty($value)){
                            $validated=false;
                        }
                        break;
                    }
                }
            }
            if(!$registered){
                $validated=false;
            }
        }

        return $validated;
    }

    protected function getQueriedContext(){
        $queriedContexts=array();
        foreach($this->parameters as $context=>$set){
            $queriedContexts[$context]=null;
            foreach($set as $id=>$properties){
                if(isset($properties['value']) &&
                    !empty($properties['value'])){
                    $queriedContexts[$context]=true;
                }elseif(isset($properties['required']) &&
                    $properties['required']===true){
                    $queriedContexts[$context]=false;
                    break;
                }
            }
        }

        if(isset($this->parameters['global']) &&
            $queriedContexts['global']===false){
            return null;
        }
        unset($queriedContexts['global']);

        switch(array_sum($queriedContexts)){
        case 0:
            foreach($queriedContexts as $context=>$queried){
                if (is_null($queried)){
                    return $context;
                }
            }
            return null;
        case 1: return array_search(true,$queriedContexts);
        default: return false;
        }
    }

    /**
    * Defined datas with parameters depending choose bridge
    * Note : you can define a cache with "setCache"
    * @param array array with expected bridge paramters
    */
    public function setDatas(array $param){
        if(!is_null($this->cache)){
            $this->cache->prepare($param);
            $time = $this->cache->getTime();
        } else {
            $time = false;
        }

        if($time !== false && (time() - $this->getCacheDuration() < $time)){
            $this->items = $this->cache->loadData();
            return;
        }

        if(empty($this->parameters) && !empty($param)){
            $this->returnClientError('Invalid parameters value(s)');
        };

        if(!$this->validateData($param)){
            $this->returnClientError('Invalid parameters value(s)');
        }

        // Populate BridgeAbstract::parameters with sanitized data
        foreach($param as $name=>$value){
            foreach($this->parameters as $context=>$set){
                if(isset($this->parameters[$context][$name]))
                    $this->parameters[$context][$name]['value']=$value;
            }
        }

        // Guess the paramter context from input data
        $queriedContext=$this->getQueriedContext();
        if(is_null($queriedContext)){
            $this->returnClientError('Required parameter(s) missing');
        }else if($queriedContext===false){
            $this->returnClientError('Mixed context parameters');
        }

        $this->queriedContext=$queriedContext;

        // Apply default values to missing data
        $contexts=array($this->queriedContext);
        if(isset($this->parameters['global'])){
            $contexts[]='global';
        }
        foreach($contexts as $context){
            foreach($this->parameters[$context] as $p=>$properties){
                if(!isset($properties['type'])){
                    $this->parameters[$context][$p]['type']='text';
                }
                if(isset($properties['value'])){
                    continue;
                }
                switch($properties['type']){
                case 'checkbox':
                    if(!isset($properties['defaultValue'])){
                        $this->parameters[$context][$p]['value']=false;
                    }else{
                        $this->parameters[$context][$p]['value']=$properties['defaultValue'];
                    }
                    break;
                }
            }
        }

        // Copy global parameter values to the guessed context
        foreach($this->parameters['global'] as $name=>$properties){
            if(isset($param[$name])){
                $value=$param[$name];
            }else if(isset($properties['value'])){
                $value=$properties['value'];
            }else{
                continue;
            }
            $this->parameters[$queriedContext][$name]['value']=$value;
        }

        $this->collectData();

        if(!is_null($this->cache)){
            $this->cache->saveData($this->getDatas());
        }
    }

    public function getName(){
        return $this->name;
    }

    public function getURI(){
        return $this->uri;
    }

    public function getCacheDuration(){
        return 3600;
    }

    public function setCache(\CacheAbstract $cache){
        $this->cache = $cache;
    }

    public function debugMessage($text){
        if(!file_exists('DEBUG')) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $calling = $backtrace[2];
        $message = $calling['file'] . ':'
        . $calling['line'] . ' class '
        . get_class($this) . '->'
        . $calling['function'] . ' - '
        . $text;

        error_log($message);
    }

    protected function getContents($url
    , $use_include_path = false
    , $context = null
    , $offset = 0
    , $maxlen = null){
        $contextOptions = array(
            'http' => array(
                'user_agent' => ini_get('user_agent')
            ),
        );

        if(defined('PROXY_URL') && $this->useProxy){
            $contextOptions['http']['proxy'] = PROXY_URL;
            $contextOptions['http']['request_fulluri'] = true;

            if(is_null($context)){
                $context = stream_context_create($contextOptions);
            } else {
                $prevContext=$context;
                if(!stream_context_set_option($context, $contextOptions)){
                    $context = $prevContext;
                }
            }
        }

        if(is_null($maxlen)){
            $content = @file_get_contents($url, $use_include_path, $context, $offset);
        } else {
            $content = @file_get_contents($url, $use_include_path, $context, $offset, $maxlen);
        }

        if($content === false)
            $this->debugMessage('Cant\'t download ' . $url);

        return $content;
    }

    protected function getSimpleHTMLDOM($url
    , $use_include_path = false
    , $context = null
    , $offset = 0
    , $maxLen = null
    , $lowercase = true
    , $forceTagsClosed = true
    , $target_charset = DEFAULT_TARGET_CHARSET
    , $stripRN = true
    , $defaultBRText = DEFAULT_BR_TEXT
    , $defaultSpanText = DEFAULT_SPAN_TEXT){
      $content = $this->getContents($url, $use_include_path, $context, $offset, $maxLen);
      return str_get_html($content
      , $lowercase
      , $forceTagsClosed
      , $target_charset
      , $stripRN
      , $defaultBRText
      , $defaultSpanText);
    }
}

/**
 * Extension of BridgeAbstract allowing caching of files downloaded over http.
 * TODO allow file cache invalidation by touching files on access, and removing
 * files/directories which have not been touched since ... a long time
 */
abstract class HttpCachingBridgeAbstract extends BridgeAbstract {

    /**
     * Maintain locally cached versions of pages to download, to avoid multiple downloads.
     * @param url url to cache
     * @return content of the file as string
     */
    public function get_cached($url){
        // TODO build this from the variable given to Cache
        $cacheDir = __DIR__ . '/../cache/pages/';
        $filepath = $this->buildCacheFilePath($url, $cacheDir);

        if(file_exists($filepath)){
            $this->debugMessage('loading cached file from ' . $filepath . ' for page at url ' . $url);
            // TODO touch file and its parent, and try to do neighbour deletion
            $this->refresh_in_cache($cacheDir, $filepath);
            $content = file_get_contents($filepath);
        } else {
            $this->debugMessage('we have no local copy of ' . $url . ' Downloading to ' . $filepath);
            $dir = substr($filepath, 0, strrpos($filepath, '/'));

            if(!is_dir($dir)){
                $this->debugMessage('creating directories for ' . $dir);
                mkdir($dir, 0777, true);
            }

            $content = $this->getContents($url);
            if($content !== false){
                file_put_contents($filepath, $content);
            }
        }

        return $content;
    }

     public function get_cached_time($url){
        // TODO build this from the variable given to Cache
        $cacheDir = __DIR__ . '/../cache/pages/';
        $filepath = $this->buildCacheFilePath($url, $cacheDir);

        if(!file_exists($filepath)){
            $this->get_cached($url);
        }

        return filectime($filepath);
    }

    private function refresh_in_cache($cacheDir, $filepath){
        $currentPath = $filepath;
        while(!$cacheDir == $currentPath){
            touch($currentPath);
            $currentPath = dirname($currentPath);
        }
    }

    private function buildCacheFilePath($url, $cacheDir){
        $simplified_url = str_replace(
            ['http://', 'https://', '?', '&', '='],
            ['', '', '/', '/', '/'],
            $url);

        if(substr($cacheDir, -1) !== '/'){
            $cacheDir .= '/';
        }

        $filepath = $cacheDir . $simplified_url;

        if(substr($filepath, -1) === '/'){
            $filepath .= 'index.html';
        }

        return $filepath;
    }

    public function remove_from_cache($url){
        // TODO build this from the variable given to Cache
        $cacheDir = __DIR__ . '/../cache/pages/';
        $filepath = $this->buildCacheFilePath($url, $cacheDir);
        $this->debugMessage('removing from cache \'' . $filepath . '\' WELL, NOT REALLY');
        // unlink($filepath);
    }
}

abstract class RssExpander extends HttpCachingBridgeAbstract {

    public function collectExpandableDatas($name){
        if(empty($name)){
            $this->returnServerError('There is no $name for this RSS expander');
        }

        $this->debugMessage('Loading from ' . $name);

        /* Notice we do not use cache here on purpose:
         * we want a fresh view of the RSS stream each time
         */
        $content = $this->getContents($name) or $this->returnServerError('Could not request ' . $name);

        $rssContent = simplexml_load_string($content);
        $this->debugMessage('loaded RSS from ' . $name);
        // TODO insert RSS format detection
        // For now we always assume RSS 2.0
        $this->collect_RSS_2_0_data($rssContent);
    }

    protected function collect_RSS_2_0_data($rssContent){
        $rssContent = $rssContent->channel[0];
        $this->debugMessage('RSS content is ===========\n' . var_export($rssContent, true) . '===========');
        $this->load_RSS_2_0_feed_data($rssContent);
        foreach($rssContent->item as $item){
            $this->debugMessage('parsing item ' . var_export($item, true));
            $this->items[] = $this->parseRSSItem($item);
        }
    }

    protected function RSS_2_0_time_to_timestamp($item){
        return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
    }

    // TODO set title, link, description, language, and so on
    protected function load_RSS_2_0_feed_data($rssContent){
        $this->name = trim($rssContent->title);
        $this->uri = trim($rssContent->link);
        $this->description = trim($rssContent->description);
    }

    /**
     * Method should return, from a source RSS item given by lastRSS, one of our Items objects
     * @param $item the input rss item
     * @return a RSS-Bridge Item, with (hopefully) the whole content)
     */
    abstract protected function parseRSSItem($item);

    public function getDescription(){
        return $this->description;
    }
}
