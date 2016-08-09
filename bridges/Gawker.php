<?php
define("RSS_PREFIX", "http://feeds.gawker.com/");
define("RSS_SUFFIX", "/full");

class Gawker extends RssExpander{

    public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Gawker media";
		$this->uri = "http://feeds.gawker.com/";
		$this->description = "A bridge allowing access to any of the numerous Gawker media blogs (Lifehacker, deadspin, Kotaku, Jezebel, and so on. Notice you have to give its id to find the RSS stream in gawker maze";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "site id to put in uri between feeds.gawker.com and /full .. which is obviously not full AT ALL",
				"identifier" : "site"
			}
		]';
	}


    private function toURI($name) {
        return RSS_PREFIX.$name.RSS_SUFFIX;
    }

    public function collectData(array $param){
        if (empty($param['site'])) {
			trigger_error("If no site is provided, nothing is gonna happen", E_USER_ERROR);
        } else {
            $this->name = $param['site'];
			$url = $this->toURI(strtolower($param['site']));
        }
//        $this->message("loading feed from ".$this->getURI());
        parent::collectExpandableDatas($param, $url);
    }
    
    protected function parseRSSItem($newsItem) {
        $item = new Item();
        $item->uri = trim($newsItem->link);
        $item->title = trim($newsItem->title);
        $item->timestamp = $this->RSS_2_0_time_to_timestamp($newsItem);
//        $this->message("///////////////////////////////////////////////////////////////////////////////////////\nprocessing item ".var_export($item, true)."\n\n\nbuilt from\n\n\n".var_export($newsItem, true));
        try {
            // now load that uri from cache
//            $this->message("loading page ".$item->uri);
            $articlePage = str_get_html($this->get_cached($item->uri));
            if(is_object($articlePage)) {
                $content = $articlePage->find('.post-content', 0);
                HTMLSanitizer::defaultImageSrcTo($content, $this->getURI());
                $vcard = $articlePage->find('.vcard', 0);
                if(is_object($vcard)) {
                    $authorLink = $vcard->find('a', 0);
    				$item->author = $authorLink->innertext;
                    // TODO use author link href to fill the feed info
                }
//                $this->message("item quite loaded : ".var_export($item, true));
                // I set item content as last element, for easier var_export reading
                $item->content = $content->innertext;
            } else {
                throw new Exception("cache content for ".$item->uri." is NOT a Simple DOM parser object !");
            }
        } catch(Exception $e) {
            $this->message("obtaining ".$item->uri." resulted in exception ".$e->getMessage().". Deleting cached page ...");
            // maybe file is incorrect. it should be discarded from cache
            $this->remove_from_cache($item->url);
            $item->content = $e->getMessage();
        }
        return $item;
    }
}
