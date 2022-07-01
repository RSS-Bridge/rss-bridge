<?php

class CommonDreamsBridge extends FeedExpander
{
    const MAINTAINER = 'nyutag';
    const NAME = 'CommonDreams Bridge';
    const URI = 'https://www.commondreams.org/';
    const DESCRIPTION = 'Returns the newest articles.';

    public function collectData()
    {
        $this->collectExpandableDatas('http://www.commondreams.org/rss.xml', 10);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        $item['content'] = $this->extractContent($item['uri']);
        return $item;
    }

    private function extractContent($url)
    {
        $html3 = getSimpleHTMLDOMCached($url);
        $text = $html3->find('div[class=field--type-text-with-summary]', 0)->innertext;
        $html3->clear();
        unset($html3);
        return $text;
    }
}
