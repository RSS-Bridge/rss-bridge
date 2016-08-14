<?php
define("SEXE", "http://sexes.blogs.liberation.fr");
define("SEXE_FEED", "http://sexes.blogs.liberation.fr/feeds/");

class Les400Culs extends RssExpander{

	public function loadMetadatas() {

		$this->maintainer = "unknown";
		$this->name = "Les 400 Culs";
		$this->uri = "http://sexes.blogs.liberation.fr";
		$this->description = "La planete sexe vue par Agnes Girard via rss-bridge";
		$this->update = "2016-08-09";

	}


    public function collectData(array $param){
        parent::collectExpandableDatas($param, SEXE_FEED);
    }
    
    protected function parseRSSItem($newsItem) {
        $item = new Item();
        $item->title = trim((string) $newsItem->title);
//        $this->message("browsing item ".var_export($newsItem, true));
        if(empty($newsItem->guid)) {
            $item->uri = (string) $newsItem->link;
        } else {
            $item->uri = (string) $newsItem->guid;
        }
        // now load that uri from cache
//        $this->message("now loading page ".$item->uri);
//        $articlePage = str_get_html($this->get_cached($item->uri));

//        $content = $articlePage->find('.post-container', 0);
        $item->content = (string) $newsItem->description;
        $item->author = (string) $newsItem->author;
        $item->timestamp = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
