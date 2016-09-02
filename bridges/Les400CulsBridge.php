<?php
class Les400CulsBridge extends RssExpander{

	const MAINTAINER = "unknown";
	const NAME = "Les 400 Culs";
	const URI = "http://sexes.blogs.liberation.fr/";
	const DESCRIPTION = "La planete sexe vue par Agnes Girard via rss-bridge";


    public function collectData(){
        $this->collectExpandableDatas(self::URI.'feeds/');
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
