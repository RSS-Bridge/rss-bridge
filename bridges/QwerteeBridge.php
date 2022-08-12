<?php

class QwerteeBridge extends BridgeAbstract
{
    const NAME = 'Qwertee';
    const URI = 'https://www.qwertee.com';
    const DESCRIPTION = 'Returns the daily designs';
    const MAINTAINER = 'Bockiii';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 60 * 60 * 3; // 3 hours

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('div.big-slides', 0)->find('div.big-slide') as $element) {
            $title = $element->find('div.index-tee', 0)->getAttribute('data-name', 0);
            $today = date('m/d/Y');
            $item = [];
            $item['uri'] = self::URI;
            $item['title'] = $title;
            $item['uid'] = $title;
            $item['timestamp'] = $today;
            $item['content'] = '<a href="'
            . $item['uri']
            . '"><img src="'
            . $element->find('img', 0)->getAttribute('src', 0)
            . '" /></a>';

            $this->items[] = $item;
        }
    }
}
