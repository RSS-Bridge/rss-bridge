<?php
/**
*
* @name The Oatmeal
* @description Un petit site de dessins assez rigolos
* @update 20/02/201403/07/2015
*/
require_once 'bridges/RssExpander.php';
define("THE_OATMEAL", "http://theoatmeal.com/");
define("RSS", "http://feeds.feedburner.com/oatmealfeed");
class TheOatmeal extends RssExpander{

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
        $this->message("now loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));

        $content = $articlePage->find('#comic', 0);
		if($content==null) {
			$content = $articlePage->find('#blog');
		}
        $item->content = $newsItem->description;
        $item->name = $newsItem->author;
        $item->timestamp = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
