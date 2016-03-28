<?php
define("THE_OATMEAL", "http://theoatmeal.com/");
define("THE_OATMEAL_RSS", "http://feeds.feedburner.com/oatmealfeed");

class TheOatmealBridge extends RssExpander{

	public function loadMetadatas() {

		$this->maintainer = "Riduidel";
		$this->name = "The Oatmeal";
		$this->uri = "http://theoatmeal.com/";
		$this->description = "Un petit site de dessins assez rigolos";
		$this->update = "2015-07-03";

	}

    public function collectData(array $param){
        parent::collectExpandableDatas($param, THE_OATMEAL_RSS);
    }


    /**
     * Since the oatmeal produces a weird RSS feed, I have to fix it by loading the items separatly from the feed infos
     */
    protected function collect_RSS_2_0_data($rssContent) {
        $rssContent->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $rssHeaderContent = $rssContent->channel[0];
//        $this->message("RSS content is ===========\n".var_export($rssHeaderContent, true)."===========");
        $this->load_RSS_2_0_feed_data($rssHeaderContent);
        foreach($rssContent->item as $item) {
            $this->message("parsing item ".var_export($item, true));
            $this->items[] = $this->parseRSSItem($item);
        }
    }


    protected function parseRSSItem($newsItem) {
        $namespaces = $newsItem->getNameSpaces(true);
        $dc = $newsItem->children($namespaces['dc']);
        $rdf = $newsItem->children($namespaces['rdf']);
        $item = new Item();
        $item->title = trim($newsItem->title);
        $this->message("browsing Oatmeal item ".var_export($newsItem, true));
        $item->uri=(string) $newsItem->attributes($namespaces['rdf'])->about;
        // now load that uri from cache
        $this->message("now loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));

        $content = $articlePage->find('#comic', 0);
		if($content==null) {
			$content = $articlePage->find('#blog');
		}
        $item->content = $content->innertext;
        
        $this->message("dc content is ".var_export($dc, true));
        $item->name = (string) $dc->creator;
        $item->timestamp = DateTime::createFromFormat(DateTime::ISO8601, $dc->date)->getTimestamp();
        $this->message("writtem by ".$item->name." on ".$item->timestamp);
        return $item;
    }
    
    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
