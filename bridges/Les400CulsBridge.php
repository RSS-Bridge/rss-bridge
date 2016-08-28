<?php
define("SEXE", "http://sexes.blogs.liberation.fr");
define("SEXE_FEED", "http://sexes.blogs.liberation.fr/feeds/");

class Les400CulsBridge extends RssExpander{

	public $maintainer = "unknown";
	public $name = "Les 400 Culs";
	public $uri = "http://sexes.blogs.liberation.fr";
	public $description = "La planete sexe vue par Agnes Girard via rss-bridge";


    public function collectData(){
        parent::collectExpandableDatas(SEXE_FEED);
    }

    protected function parseRSSItem($newsItem) {
        $item = array();
        $item['title'] = trim((string) $newsItem->title);
        $this->debugMessage("browsing item ".var_export($newsItem, true));
        if(empty($newsItem->guid)) {
            $item['uri'] = (string) $newsItem->link;
        } else {
            $item['uri'] = (string) $newsItem->guid;
        }
        // now load that uri from cache
        $this->debugMessage("now loading page ".$item['uri']);
//        $articlePage = $this->get_cached($item['uri']);

//        $content = $articlePage->find('.post-container', 0);
        $item['content'] = (string) $newsItem->description;
        $item['author'] = (string) $newsItem->author;
        $item['timestamp'] = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
