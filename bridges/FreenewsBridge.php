<?php
define("FREENEWS_RSS", 'http://feeds.feedburner.com/Freenews-Freebox?format=xml');
class FreenewsBridge extends FeedExpander {

	const MAINTAINER = "mitsukarenai";
	const NAME = "Freenews";
	const URI = "http://freenews.fr";
	const DESCRIPTION = "Un site d'actualité pour les freenautes (mais ne parlant pas que de la freebox). Ne rentrez pas d'id si vous voulez accéder aux actualités générales.";

    public function collectData(){
        parent::collectExpandableDatas(FREENEWS_RSS);
    }

    protected function parseItem($newsItem) {
        $item = array();
        $item['title'] = trim($newsItem->title);
        $this->debugMessage("item has for title \"".$item['title']."\"");
        if(empty($newsItem->guid)) {
            $item['uri'] = (string) $newsItem->link;
        } else {
            $item['uri'] = (string) $newsItem->guid;
        }
        // now load that uri from cache
        $this->debugMessage("now loading page ".$item['uri']);
        $articlePage = $this->get_cached($item['uri']);

        $content = $articlePage->find('.post-container', 0);
        $item['content'] = $content->innertext;
        $item['author'] = $articlePage->find('a[rel=author]', 0)->innertext;
        // format should parse 2014-03-25T16:21:20Z. But, according to http://stackoverflow.com/a/10478469, it is not that simple
        $item['timestamp'] = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
}
