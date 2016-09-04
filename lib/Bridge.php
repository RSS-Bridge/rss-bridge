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

    const NAME = 'Unnamed bridge';
    const URI = '';
    const DESCRIPTION = 'No description provided';
    const MAINTAINER = 'No maintainer';
    const PARAMETERS = array();

    public $useProxy = true;

    protected $cache;
    protected $items = array();
    protected $inputs = array();
    protected $queriedContext = '';

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
    public function getItems(){
        return $this->items;
    }

    protected function validateTextValue($value, $pattern = null){
        if(!is_null($pattern)){
            $filteredValue = filter_var($value, FILTER_VALIDATE_REGEXP,
                array('options' => array(
                    'regexp' => '/^' . $pattern . '$/'
                ))
            );
        } else {
            $filteredValue = filter_var($value);
        }

        if($filteredValue === false)
            return null;

        return $filteredValue;
    }

    protected function validateNumberValue($value){
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT);

        if($filteredValue === false && !empty($value))
            return null;

        return $filteredValue;
    }

    protected function validateCheckboxValue($value){
        $filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if(is_null($filteredValue))
            return null;

        return $filteredValue;
    }

    protected function validateListValue($value, $expectedValues){
        $filteredValue = filter_var($value);

        if($filteredValue === false)
            return null;

        if(!in_array($filteredValue, $expectedValues)){ // Check sub-values?
            foreach($expectedValues as $subName => $subValue){
                if(is_array($subValue) && in_array($filteredValue, $subValue))
                    return $filteredValue;
            }
            return null;
        }

        return $filteredValue;
    }

    protected function validateData(&$data){
        if(!is_array($data))
            return false;

        foreach($data as $name=>$value){
            $registered = false;
            foreach(static::PARAMETERS as $context=>$set){
                if(array_key_exists($name,$set)){
                    $registered = true;
                    if(!isset($set[$name]['type'])){
                        $set[$name]['type']='text';
                    }

                    switch($set[$name]['type']){
                    case 'number':
                        $data[$name] = $this->validateNumberValue($value);
                        break;
                    case 'checkbox':
                        $data[$name] = $this->validateCheckboxValue($value);
                        break;
                    case 'list':
                        $data[$name] = $this->validateListValue($value, $set[$name]['values']);
                        break;
                    default:
                    case 'text':
                        if(isset($set[$name]['pattern'])){
                            $data[$name] = $this->validateTextValue($value, $set[$name]['pattern']);
                        } else {
                            $data[$name] = $this->validateTextValue($value);
                        }
                        break;
                    }

                    if(is_null($data[$name])){
                        echo 'Parameter \'' . $name . '\' is invalid!' . PHP_EOL;
                        return false;
                    }
                }
            }

            if(!$registered)
                return false;
        }

        return true;
    }

    protected function setInputs(array $inputs, $queriedContext){
        // Import and assign all inputs to their context
        foreach($inputs as $name => $value){
            foreach(static::PARAMETERS as $context => $set){
                if(array_key_exists($name, static::PARAMETERS[$context])){
                    $this->inputs[$context][$name]['value'] = $value;
                }
            }
        }

        // Apply default values to missing data
        $contexts = array($queriedContext);
        if(array_key_exists('global', static::PARAMETERS)){
            $contexts[] = 'global';
        }

        foreach($contexts as $context){
            foreach(static::PARAMETERS[$context] as $name => $properties){
                if(isset($this->inputs[$context][$name]['value'])){
                    continue;
                }

                $type = isset($properties['type']) ? $properties['type'] : 'text';

                switch($type){
                case 'checkbox':
                    if(!isset($properties['defaultValue'])){
                        $this->inputs[$context][$name]['value'] = false;
                    } else {
                        $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                    }
                    break;
                case 'list':
                    if(!isset($properties['defaultValue'])){
                        $firstItem = reset($properties['values']);
                        if(is_array($firstItem)){
                            $firstItem = reset($firstItem);
                        }
                        $this->inputs[$context][$name]['value'] = $firstItem;
                    } else {
                        $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                    }
                    break;
                default:
                    if(isset($properties['defaultValue'])){
                        $this->inputs[$context][$name]['value'] = $properties['defaultValue'];
                    }
                    break;
                }
            }
        }

        // Copy global parameter values to the guessed context
        if(array_key_exists('global', static::PARAMETERS)){
            foreach(static::PARAMETERS['global'] as $name => $properties){
                if(isset($inputs[$name])){
                    $value = $inputs[$name];
                } elseif (isset($properties['value'])){
                    $value = $properties['value'];
                } else {
                    continue;
                }
                $this->inputs[$queriedContext][$name]['value'] = $value;
            }
        }

        // Only keep guessed context parameters values
        if(isset($this->inputs[$queriedContext])){
            $this->inputs = array($queriedContext => $this->inputs[$queriedContext]);
        } else {
            $this->inputs = array();
        }
    }

    protected function getQueriedContext(array $inputs){
        $queriedContexts=array();
        foreach(static::PARAMETERS as $context=>$set){
            $queriedContexts[$context]=null;
            foreach($set as $id=>$properties){
                if(isset($inputs[$id]) && !empty($inputs[$id])){
                    $queriedContexts[$context]=true;
                }elseif(isset($properties['required']) &&
                    $properties['required']===true){
                    $queriedContexts[$context]=false;
                    break;
                }
            }
        }

        if(array_key_exists('global',static::PARAMETERS) &&
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
    public function setDatas(array $inputs){
        if(!is_null($this->cache)){
            $this->cache->prepare($inputs);
            $time = $this->cache->getTime();
            if($time !== false && (time() - $this->getCacheDuration() < $time)){
                $this->items = $this->cache->loadData();
                return;
            }
        }

        if(empty(static::PARAMETERS)){
            if(!empty($inputs)){
                $this->returnClientError('Invalid parameters value(s)');
            }

            $this->collectData();
            if(!is_null($this->cache)){
                $this->cache->saveData($this->getItems());
            }
            return;
        }

        if(!$this->validateData($inputs)){
            $this->returnClientError('Invalid parameters value(s)');
        }

        // Guess the paramter context from input data
        $this->queriedContext = $this->getQueriedContext($inputs);
        if(is_null($this->queriedContext)){
            $this->returnClientError('Required parameter(s) missing');
        } elseif($this->queriedContext === false){
            $this->returnClientError('Mixed context parameters');
        }

        $this->setInputs($inputs, $this->queriedContext);

        $this->collectData();

        if(!is_null($this->cache)){
            $this->cache->saveData($this->getItems());
        }
    }

    function getInput($input){
        if(!isset($this->inputs[$this->queriedContext][$input]['value'])){
            return null;
        }
        return $this->inputs[$this->queriedContext][$input]['value'];
    }

    public function getName(){
        return static::NAME;
    }

    public function getURI(){
        return static::URI;
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

        return str_get_html($content);
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
        $this->debugMessage('removing from cache \'' . $filepath . '\'');
        unlink($filepath);
    }
}

abstract class FeedExpander extends HttpCachingBridgeAbstract {

  private $name;
  private $uri;
  private $description;

    public function collectExpandableDatas($url){
        if(empty($url)){
            $this->returnServerError('There is no $url for this RSS expander');
        }

        $this->debugMessage('Loading from ' . $url);

        /* Notice we do not use cache here on purpose:
         * we want a fresh view of the RSS stream each time
         */
        $content = $this->getContents($url) 
            or $this->returnServerError('Could not request ' . $url);
        $rssContent = simplexml_load_string($content);

        $this->debugMessage('Detecting feed format/version');
        if(isset($rssContent->channel[0])){
            $this->debugMessage('Detected RSS format');
            if(isset($rssContent->item[0])){
                $this->debugMessage('Detected RSS 1.0 format');
                $this->collect_RSS_1_0_data($rssContent);
            } else {
                $this->debugMessage('Detected RSS 0.9x or 2.0 format');
                $this->collect_RSS_2_0_data($rssContent);
            }
        } elseif(isset($rssContent->entry[0])){
            $this->debugMessage('Detected ATOM format');
            $this->collect_ATOM_data($rssContent);
        } else {
            $this->debugMessage('Unknown feed format/version');
            $this->returnServerError('The feed format is unknown!');
        }
    }

    protected function collect_RSS_1_0_data($rssContent){
        $this->load_RSS_2_0_feed_data($rssContent->channel[0]);
        foreach($rssContent->item as $item){
            $this->debugMessage('parsing item ' . var_export($item, true));
            $this->items[] = $this->parseItem($item);
        }
    }

    protected function collect_RSS_2_0_data($rssContent){
        $rssContent = $rssContent->channel[0];
        $this->debugMessage('RSS content is ===========\n' . var_export($rssContent, true) . '===========');
        $this->load_RSS_2_0_feed_data($rssContent);
        foreach($rssContent->item as $item){
            $this->debugMessage('parsing item ' . var_export($item, true));
            $this->items[] = $this->parseItem($item);
        }
    }

    protected function collect_ATOM_data($content){
        $this->load_ATOM_feed_data($content);
        foreach($content->entry as $item){
            $this->debugMessage('parsing item ' . var_export($item, true));
            $this->items[] = $this->parseItem($item);
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

    protected function load_ATOM_feed_data($content){
        $this->name = $content->title;

        // Find best link (only one, or first of 'alternate')
        if(!isset($content->link)){
            $this->uri = '';
        } elseif (count($content->link) === 1){
            $this->uri = $content->link[0]['href'];
        } else {
            $this->uri = '';
            foreach($content->link as $link){
                if(strtolower($link['rel']) === 'alternate'){
                    $this->uri = $link['href'];
                    break;
                }
            }
        }

        if(isset($content->subtitle))
            $this->description = $content->subtitle;
    }

    protected function parseATOMItem($feedItem){
        $item = array();
        if(isset($feedItem->id)) $item['uri'] = $feedItem->id;
        if(isset($feedItem->title)) $item['title'] = $feedItem->title;
        if(isset($feedItem->updated)) $item['timestamp'] = strtotime($feedItem->updated);
        if(isset($feedItem->author)) $item['author'] = $feedItem->author->name;
        if(isset($feedItem->content)) $item['content'] = $feedItem->content;
        return $item;
    }

    protected function parseRSS_0_9_1_Item($feedItem){
        $item = array();
        if(isset($feedItem->link)) $item['uri'] = $feedItem->link;
        if(isset($feedItem->title)) $item['title'] = $feedItem->title;
        // rss 0.91 doesn't support timestamps
        // rss 0.91 doesn't support authors
        if(isset($feedItem->description)) $item['content'] = $feedItem->description;
        return $item;
    }

    protected function parseRSS_1_0_Item($feedItem){
        // 1.0 adds optional elements around the 0.91 standard
        $item = $this->parseRSS_0_9_1_Item($feedItem);

        $namespaces = $feedItem->getNamespaces(true);
        if(isset($namespaces['dc'])){
            $dc = $feedItem->children($namespaces['dc']);
            if(isset($dc->date)) $item['timestamp'] = strtotime($dc->date);
            if(isset($dc->creator)) $item['author'] = $dc->creator;
        }

        return $item;
    }

    protected function parseRSS_2_0_Item($feedItem){
        // Primary data is compatible to 0.91
        $item = $this->parseRSS_0_9_1_Item($feedItem);
        if(isset($feedItem->pubDate)) $item['timestamp'] = strtotime($feedItem->pubDate);
        if(isset($feedItem->author)){
            $item['author'] = $feedItem->author;
        } else {
            // Feed might use 'dc' namespace
            $namespaces = $feedItem->getNamespaces(true);
            if(isset($namespaces['dc'])){
                $dc = $feedItem->children($namespaces['dc']);
                if(isset($dc->creator)) $item['author'] = $dc->creator;
            }
        }
        return $item;
    }

    /**
     * Method should return, from a source RSS item given by lastRSS, one of our Items objects
     * @param $item the input rss item
     * @return a RSS-Bridge Item, with (hopefully) the whole content)
     */
    abstract protected function parseItem($item);

    public function getURI(){
      return $this->uri;
    }

    public function getName(){
      return $this->name;
    }

    public function getDescription(){
        return $this->description;
    }
}
