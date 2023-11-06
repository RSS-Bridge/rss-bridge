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

    protected function parseItem(array $item)
    {
        $item['content'] = $this->extractContent($item['uri']);
        return $item;
    }

    private function extractContent($url)
    {
        $dom = getSimpleHTMLDOMCached($url);
        $summary = $dom->find('div.node__body', 0);
        $text = $summary->innertext;
        $dom->clear();
        unset($dom);
        return $text;
    }
}
