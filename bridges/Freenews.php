<?php
define("FREENEWS_RSS", 'http://feeds.feedburner.com/Freenews-Freebox?format=xml');
class Freenews extends RssExpander {

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Freenews";
		$this->uri = "http://freenews.fr";
		$this->description = "Un site d'actualité pour les freenautes (mais ne parlant pas que de la freebox). Ne rentrez pas d'id si vous voulez accéder aux actualités générales.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "Id de la rubrique (sans le \'-\')",
				"identifier" : "id"
			}
		]';
	}

    public function collectData(array $param){
        parent::collectExpandableDatas($param, FREENEWS_RSS);
    }
    
    protected function parseRSSItem($newsItem) {
        $item = new Item();
        $item->title = trim($newsItem->title);
//        $this->message("item has for title \"".$item->title."\"");
        if(empty($newsItem->guid)) {
            $item->uri = (string) $newsItem->link;
        } else {
            $item->uri = (string) $newsItem->guid;
        }
        // now load that uri from cache
//        $this->message("now loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));

        $content = $articlePage->find('.post-container', 0);
        $item->content = $content->innertext;
        $item->author = $articlePage->find('a[rel=author]', 0)->innertext;
        // format should parse 2014-03-25T16:21:20Z. But, according to http://stackoverflow.com/a/10478469, it is not that simple
        $item->timestamp = $this->RSS_2_0_time_to_timestamp($newsItem);
        return $item;
    }
}
