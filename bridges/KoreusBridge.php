<?php

class KoreusBridge extends FeedExpander
{
    const MAINTAINER = 'pit-fgfjiudghdf';
    const NAME = 'Koreus';
    const URI = 'https://www.koreus.com/';
    const DESCRIPTION = 'Returns the newest posts from Koreus (full text)';

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $text = $html->find('p.itemText', 0)->innertext;
        $item['content'] = utf8_encode($text);

        return $item;
    }

    public function collectData()
    {
        $this->collectExpandableDatas('https://feeds.feedburner.com/Koreus-articles');
    }
}
