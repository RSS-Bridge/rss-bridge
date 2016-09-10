<?php
class FreenewsBridge extends FeedExpander {

    const MAINTAINER = "mitsukarenai";
    const NAME = "Freenews";
    const URI = "http://freenews.fr";
    const DESCRIPTION = "Un site d'actualité pour les freenautes (mais ne parlant pas que de la freebox). Ne rentrez pas d'id si vous voulez accéder aux actualités générales.";

    public function collectData(){
        parent::collectExpandableDatas('http://feeds.feedburner.com/Freenews-Freebox?format=xml');
    }

    protected function parseItem($newsItem) {
        $item = $this->parseRSS_2_0_Item($newsItem);
        
        $articlePage = $this->getSimpleHTMLDOMCached($item['uri']);
        $content = $articlePage->find('.post-container', 0);
        $item['content'] = $content->innertext;

        return $item;
    }
}
