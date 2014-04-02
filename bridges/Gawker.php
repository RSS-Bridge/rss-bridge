<?php
/**
*
* @name Gawker media
* @description A bridge allowing access to any of the numerous Gawker media blogs (Lifehacker, deadspin, Kotaku, Jezebel, and so on
* @update 27/03/2014
* @use1(site="site")
*/
class Gawker extends HttpCachingBridgeAbstract{
	private $uri;
	private $name;

    public function collectData(array $param){
        if (empty($param['site'])) {
			trigger_error("If no site is provided, nothing is gonna happen", E_USER_ERROR);
        } else {
			$this->uri = $param['site'];
        }
        $html = file_get_html($this->getURI()) or $this->returnError('Could not request '.$this->getURI(), 404);
        $this->message("loaded HTML from ".$this->getURI());
        // customize name 
        $this->name = $html->find('title', 0)->innertext;
        foreach($html->find('.main-column') as $content) {
            $this->parseContent($content);
       }
    }

	public function parseContent($content) {
		foreach($content->find('.headline') as $headline) {
			foreach($headline->find('a') as $articleLink) {
                // notice we only use article from this gawker site (as gawker like to see us visit other sites)
                if(strpos($articleLink->href, $this->getURI())>=0) {
    				$this->parseLink($articleLink);
                }
			}
		}
	}
    
    public function parseLink($infoLink) {
        $item = new Item();
        $item->uri = $infoLink->href;
        $item->title = $infoLink->innertext;
        try {
            // now load that uri from cache
//            $this->message("loading page ".$item->uri);
            $articlePage = str_get_html($this->get_cached($item->uri));
            if(is_object($articlePage)) {
                $content = $articlePage->find('.post-content', 0);
                $this->defaultImageSrcTo($content, $this->getURI());
                $item->content = $content->innertext;
                // http://stackoverflow.com/q/22715928/15619
                $publishtime = $articlePage->find('.publish-time', 0)->getAttribute("data-publishtime");
                // don't know what I'm doing there, but http://www.epochconverter.com/programming/functions-php.php#epoch2date recommends it
                $item->timestamp = $this->js_to_unix_timestamp($publishtime);
                $vcard = $articlePage->find('.vcard', 0);
                if(is_object($vcard)) {
    				$item->name = $vcard->find('a', 0)->innertext;
                }
            } else {
                throw new Exception("cache content for ".$item->uri." is NOT a Simple DOM parser object !");
            }
        } catch(Exception $e) {
            $this->message("obtaining ".$item->uri." resulted in exception ".$e->getMessage().". Deleting cached page ...");
            // maybe file is incorrect. it should be discarded from cache
            $this->remove_from_cache($item->url);
            $item->content = $e->getMessage();
        }
        $this->items[] = $item;
    }

	function js_to_unix_timestamp($jsTimestamp){
	  return $jsTimestamp/1000; 
	}	

    public function getName(){
        return $this->name;
    }

    public function getURI(){
        return $this->uri;
    }

    public function getCacheDuration(){
        return 3600; // 1h
    }
    public function getDescription(){
        return "Gawker press blog content.";
    }
}
