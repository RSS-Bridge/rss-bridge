<?php
class TheOatmealBridge extends RssExpander{

	const MAINTAINER = "Riduidel";
	const NAME = "The Oatmeal";
	const URI = "http://theoatmeal.com/";
	const DESCRIPTION = "Un petit site de dessins assez rigolos";

    public function collectData(){
        $this->collectExpandableDatas('http://feeds.feedburner.com/oatmealfeed');
    }

    protected function parseRSSItem($newsItem) {
        $item = $this->parseRSS_1_0_Item($newsItem);

        $articlePage = $this->get_cached($item['uri']);
        $content = $articlePage->find('#comic', 0);
        if(is_null($content)) // load alternative
            $content = $articlePage->find('#blog', 0);

        if(!is_null($content))
            $item['content'] = $content->innertext;

        return $item;
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
