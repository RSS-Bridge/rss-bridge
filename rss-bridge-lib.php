<?php
/* rss-bridge library.
   Foundation functions for rss-bridge project.
   See https://github.com/sebsauvage/rss-bridge
   Licence: Public domain.
*/
ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:20.0) Gecko/20100101 Firefox/20.0');
date_default_timezone_set('UTC');
error_reporting(0);
//ini_set('display_errors','1'); error_reporting(E_ALL);  // For debugging only.
define('CACHEDIR','cache/');   // Directory containing cache files. Do not forget trailing slash.
ob_start(); 

// Create cache directory if it does not exist.
if (!is_dir(CACHEDIR)) { mkdir(CACHEDIR,0705); chmod(CACHEDIR,0705); }

// Import DOM library.
if (!file_exists('simple_html_dom.php')) 
{ 
    header('HTTP/1.1 500 Internal Server Error'); 
    header('Content-Type: text/plain'); 
    die('"PHP Simple HTML DOM Parser" is missing. Get it from http://simplehtmldom.sourceforge.net/ and place the script "simple_html_dom.php" in the same folder to allow me to work.'); 
}
require_once('simple_html_dom.php');

/**
 * Abstract RSSBridge class on which all bridges are build upon.
 * It provides utility methods (cache, ATOM feed building...)
 */
abstract class RssBridgeAbstractClass 
{
    /**
     * $items is an array of dictionnaries. Each subclass must fill this array when collectData() is called.
     * eg. $items = Array(   Array('uri'=>'http://foo.bar', 'title'=>'My beautiful foobar', 'content'='Hello, <b>world !</b>','timestamp'=>'1375864834'),
     *                       Array('uri'=>'http://toto.com', 'title'=>'Welcome to toto', 'content'='What is this website about ?','timestamp'=>'1375868313')
     *                   )
     * Keys in dictionnaries:
     *    uri (string;mandatory) = The URI the item points to.
     *    title (string;mandatory) = Title of item
     *    content (string;optionnal) = item content (usually HTML code)
     *    timestamp (string;optionnal) = item date. Must be in EPOCH format.
     *    Other keys can be added, but will be ignored.
     * $items will be used to build the ATOM feed, json and other outputs.
     */
    var $items;
    
    private $contentType;  // MIME type returned to browser.
    
    /**
     * Sets the content-type returns to browser.
     * Example: $this->setContentType('text/html; charset=UTF-8')
     */
    private function setContentType($value)
    {
        $this->contentType = $value;
        header('Content-Type: '.$value);
    }
    
    /**
     * collectData() will be called to ask the bridge to go collect data on the net.
     * All derived classes must implement this method.
     * This method must fill $this->items with collected items.
     * Input: $request : The incoming request (=$_GET). This can be used or ignored by the bridge.
     */
    abstract protected function collectData($request);

    /**
     * Returns a HTTP error to user, with a message.
     * Example: $this->returnError('404 Not Found', 'ERROR: no results.');
     */
    protected function returnError($code, $message)
    { 
        header("HTTP/1.1 $code"); header('Content-Type: text/plain;charset=UTF-8');
        die($message); 
    }
    
    /**
     * Builds an ATOM feed from $this->items and return it to browser.
     */
    private function returnATOM()
    {
        $this->setContentType('application/atom+xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">'."\n";
        echo '<title type="text">'.htmlspecialchars($this->bridgeName).'</title>'."\n";
        echo '<id>http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '')."://{$_SERVER['HTTP_HOST']}{$_SERVER['PATH_INFO']}".'/</id>'."\n";
        echo '<updated></updated>'."\n"; // FIXME
        echo '<link rel="alternate" type="text/html" href="'.htmlspecialchars($this->bridgeURI).'" />'."\n";
        echo '<link rel="self" href="http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '')."://{$_SERVER['HTTP_HOST']}".htmlentities($_SERVER['REQUEST_URI']).'" />'."\n"."\n";

        foreach($this->items as $item) {
            echo '<entry><author><name>'.htmlspecialchars($this->bridgeName).'</name><uri>'.htmlspecialchars($this->bridgeURI).'</uri></author>'."\n";
            echo '<title type="html"><![CDATA['.$item['title'].']]></title>'."\n";
            echo '<link rel="alternate" type="text/html" href="'.$item['uri'].'" />'."\n";
            echo '<id>'.$item['uri'].'</id>'."\n";
            if (isset($item['timestamp']))
            {
                echo '<updated>'.date(DATE_ATOM, $item['timestamp']).'</updated>'."\n";
                
            }
            else
            {
                echo '<updated></updated>'."\n";
            }
            if (isset($item['content']))
            {
                echo '<content type="html"><![CDATA['.$item['content'].']]></content>'."\n";
            }
            else
            {
                echo '<content type="html"></content>'."\n";
            }
            // FIXME: Security: Disable Javascript ?
            echo '</entry>'."\n\n";
            }
        echo '</feed>';    
    }
    
    private function returnHTML()
    {
        $this->setContentType('text/html; charset=UTF-8');
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
    private function returnJSON()
    {
        $this->setContentType('application/json'); 
        echo json_encode($this->items);
    }
    
    /**
     * Returns $this->items as raw php data.
     */
    private function returnPlaintext()
    {
        $this->setContentType('text/plain;charset=UTF-8'); 
        print_r($this->items); 
    }
    
    /**
     * Start processing request and return response to browser.
     */
    public function process()
    {
        $this->serveCachedVersion();

        // Cache file does not exists or has expired: We re-fetch the results and cache it.
        $this->collectData($_GET);
        if (empty($this->items)) { $this->returnError('404 Not Found', 'ERROR: no results.'); }

        $format = 'atom';
        if (!empty($_GET['format'])) { $format = $_GET['format']; }
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

    /**
     * Returns the cached version of current request URI directly to the browser
     * if it exists and if cache has not expired.
     * Continues execution no cached version available.
     */
    private function serveCachedVersion()
    {
        // See if cache exists for this request
        $cachefile = CACHEDIR.hash('sha1',$_SERVER['REQUEST_URI']).'.cache'; // Cache path and filename
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
     */
    private function storeReponseInCache()
    {
        $cachefile = CACHEDIR.hash('sha1',$_SERVER['REQUEST_URI']).'.cache'; // Cache path and filename
        $data = Array('data'=>ob_get_contents(), 'Content-Type'=>$this->contentType);
        file_put_contents($cachefile,json_encode($data));
        ob_end_flush();
    }
}

?>