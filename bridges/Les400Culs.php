<?php
/**
*
* @name Les 400 Culs 
* @description La planète sexe vue par Agnès Girard via rss-bridge
* @update 20/02/2014
*/
require_once 'bridges/RssExpander.php';
define("SEXE", "http://sexes.blogs.liberation.fr");
define("RSS", "http://sexes.blogs.liberation.fr/feeds/");
/**
 * As it seems that Les 400 culs currently offer a full feed, we won't change it content here.
 * But I'm ready for the day where it will ... again ... provide some truncated content
 */
class Les400Culs extends RssExpander{

    public function collectData(array $param){
        $param['url'] = RSS;
        parent::collectData($param);
    }
    
    protected function parseRSSItem($newsItem) {
        $item = new Item();
        $item->title = trim($newsItem->title);
//        $this->message("browsing item ".var_export($newsItem, true));
        if(empty($newsItem->guid)) {
            $item->uri = $newsItem->link;
        } else {
            $item->uri = $newsItem->guid;
        }
        // now load that uri from cache
//        $this->message("now loading page ".$item->uri);
//        $articlePage = str_get_html($this->get_cached($item->uri));

//        $content = $articlePage->find('.post-container', 0);
        $item->content = $newsItem->description;
        $item->name = $newsItem->author;
        $item->timestamp = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
