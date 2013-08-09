<?php
/* rss-bridge library.
   Foundation functions for rss-bridge project.
   See https://github.com/sebsauvage/rss-bridge
   Licence: Public domain.
*/
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:20.0) Gecko/20100101 Firefox/20.0');
date_default_timezone_set('UTC');
error_reporting(0);
ini_set('display_errors','1'); error_reporting(E_ALL);  // For debugging only.
define('CACHEDIR','cache/');   // Directory containing cache files. Do not forget trailing slash.
define('CHARSET', 'UTF-8');
define('SimpleDomLib', 'vendor/simplehtmldom/simple_html_dom.php');

ob_start(); 

// Create cache directory if it does not exist.
if (!is_dir(CACHEDIR)) { mkdir(CACHEDIR,0705); chmod(CACHEDIR,0705); }

// Import DOM library.
if (!file_exists(SimpleDomLib)) 
{ 
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain'); 
    die('"PHP Simple HTML DOM Parser" is missing. Get it from http://simplehtmldom.sourceforge.net and place the script "simple_html_dom.php" in the same folder to allow me to work.'); 
}
require_once(SimpleDomLib);

/**
 * Abstract RSSBridge class on which all bridges are build upon.
 * It provides utility methods (cache, ATOM feed building...)
 */
abstract class RssBridgeAbstractClass {
    /**
     * $items is an array of dictionnaries. Each subclass must fill this array when collectData() is called.
     * eg. $items = array(   array('uri'=>'http://foo.bar', 'title'=>'My beautiful foobar', 'content'='Hello, <b>world !</b>','timestamp'=>'1375864834'),
     *                       array('uri'=>'http://toto.com', 'title'=>'Welcome to toto', 'content'='What is this website about ?','timestamp'=>'1375868313')
     *                   )
     * Keys in dictionnaries:
     *    uri (string;mandatory) = The URI the item points to.
     *    title (string;mandatory) = Title of item
     *    content (string;optionnal) = item content (usually HTML code)
     *    timestamp (string;optionnal) = item date. Must be in EPOCH format.
     *    Other keys can be added, but will be ignored.
     * $items will be used to build the ATOM feed, json and other outputs.
     */
    public $items;
    
    private $contentType;  // MIME type returned to browser.

    /**
     * Sets the content-type returns to browser.
     * 
     * @param string Content-type returns to browser - Example: $this->setContentType('text/html; charset=UTF-8')
     * @return this
     */
    private function setContentType($value){
        $this->contentType = $value;
        header('Content-Type: '.$value);
        return $this;
    }
    
    /**
     * collectData() will be called to ask the bridge to go collect data on the net.
     * All derived classes must implement this method.
     * This method must fill $this->items with collected items.
     * @param mixed $request : The incoming request (=$_GET). This can be used or ignored by the bridge.
     */
    abstract protected function collectData($request);

    /**
     * Returns a HTTP error to user, with a message.
     * Example: $this->returnError(404, 'no results.');
     * @param integer $code
     * @param string $message
     */
    protected function returnError($code, $message){
        $errors = array(
            400 => 'Bad Request',
            404 => 'Not Found',
            501 => 'Not Implemented',
        );

        header('HTTP/1.1 ' . $code . ( isset($errors[$code]) ? ' ' . $errors[$code] : ''));
        header('Content-Type: text/plain;charset=' . CHARSET);
        die('ERROR : ' . $message); 
    }

    /**
     * Builds an ATOM feed from $this->items and return it to browser.
     */
    private function returnATOM(){
        $this->setContentType('application/atom+xml; charset=' . CHARSET);

        $https = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '' );
        $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $httpInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

        echo '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">'."\n";

        echo '<title type="text">'.htmlspecialchars($this->bridgeName).'</title>'."\n";
        echo '<id>http' . $https . '://' . $httpHost . $httpInfo . './</id>'."\n";
        echo '<updated></updated>'."\n"; // FIXME
        echo '<link rel="alternate" type="text/html" href="'.htmlspecialchars($this->bridgeURI).'" />'."\n";
        echo '<link rel="self" href="http'.$https.'://' . $httpHost . htmlentities($_SERVER['REQUEST_URI']).'" />'."\n"."\n";

        foreach($this->items as $item) {
            echo '<entry><author><name>'.htmlspecialchars($this->bridgeName).'</name><uri>'.htmlspecialchars($this->bridgeURI).'</uri></author>'."\n";
            echo '<title type="html"><![CDATA['.$item['title'].']]></title>'."\n";
            echo '<link rel="alternate" type="text/html" href="'.$item['uri'].'" />'."\n";
            echo '<id>'.$item['uri'].'</id>'."\n";
            echo '<updated>' . ( isset($item['timestamp']) ? date(DATE_ATOM, $item['timestamp']) : '' ) . '</updated>'."\n";
            echo '<content type="html">' . ( isset($item['content']) ? '<![CDATA[' . $item['content'] . ']]>' : '') . '</content>'."\n";

            // FIXME: Security: Disable Javascript ?
            echo '</entry>'."\n\n";
        }

        echo '</feed>';    
    }
    
    private function returnHTML(){
        $this->setContentType('text/html; charset=' . CHARSET);
        echo '<html><head><title>'.htmlspecialchars($this->bridgeName).'</title>';
        echo '<style>body{font-family:"Trebuchet MS",Verdana,Arial,Helvetica,sans-serif;font-size:10pt;background-color:#aaa;}div.rssitem{border:1px solid black;padding:5px;margin:10px;background-color:#fff;}</style></head><body>';
        echo '<h1>'.htmlspecialchars($this->bridgeName).'</h1>';
        foreach($this->items as $item) {
            echo '<div class="rssitem"><h2><a href="'.$item['uri'].'">'.htmlspecialchars(strip_tags($item['title'])).'</a></h2>';
            if (isset($item['timestamp'])) { echo '<small>'.date(DATE_ATOM, $item['timestamp']).'</small>'; }
            if (isset($item['content'])) { echo '<p>'.$item['content'].'</p>'; }

            echo "</div>\n\n";
        }
        echo '</body></html>';
    }
    
    /**
     * Builds a JSON string from $this->items and return it to browser.
     */   
    private function returnJSON(){
        $this->setContentType('application/json'); 
        echo json_encode($this->items);
    }
    
    /**
     * Returns $this->items as raw php data.
     */
    private function returnPlaintext(){
        $this->setContentType('text/plain;charset=' . CHARSET); 
        print_r($this->items); 
    }
    
    /**
     * Start processing request and return response to browser.
     */
    public function process(){
        $this->serveCachedVersion();

        // Cache file does not exists or has expired: We re-fetch the results and cache it.
        $this->collectData($_REQUEST);

        if (empty($this->items)) { $this->returnError(404, 'no results.'); }

        $format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'atom';
        switch($format) {
            case 'plaintext':
                $this->returnPlaintext();
                break;
            case 'json':
                $this->returnJSON();
                break;               
            case 'html':
                $this->returnHTML();
                break;              
            default:
                $this->returnATOM();
        }
        
        $this->storeReponseInCache();
    }

    private function getCacheName(){
        if( !isset($_REQUEST) ){
            $this->returnError(501, 'WTF ?');
        }

        $stringToEncode = $_SERVER['REQUEST_URI'] . http_build_query($_REQUEST);
        return CACHEDIR.hash('sha1',$stringToEncode).'.cache';
    }

    /**
     * Returns the cached version of current request URI directly to the browser
     * if it exists and if cache has not expired.
     * Continues execution no cached version available.
     */
    private function serveCachedVersion(){
        // See if cache exists for this request
        $cachefile = $this->getCacheName(); // Cache path and filename
        if (file_exists($cachefile)) { // The cache file exists.
            if (time() - ($this->cacheDuration*60) < filemtime($cachefile)) { // Cache file has not expired. Serve it.
                $data = json_decode(file_get_contents($cachefile),true);
                header('Content-Type: '.$data['Content-Type']); // Send proper MIME Type
                header('X-Cached-Version: '.date(DATE_ATOM, filemtime($cachefile)));
                echo $data['data'];
                exit();
            }
        }     
    }

    /**
     * Stores currently generated page in cache.
     * @return this
     */
    private function storeReponseInCache(){
        $cachefile = $this->getCacheName(); // Cache path and filename
        $data = array('data'=>ob_get_contents(), 'Content-Type'=>$this->contentType);
        file_put_contents($cachefile,json_encode($data));
        ob_end_flush();
        return $this;
    }
}