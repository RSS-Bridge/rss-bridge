<?php

class TeefuryBridge extends BridgeAbstract
{
    const NAME = 'Teefury';
    const URI = 'https://www.teefury.com';
    const DESCRIPTION = 'Returns the daily designs';
    const MAINTAINER = 'Bockiii';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 60 * 60 * 3; // 3 hours

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);
        $html = defaultLinkTo($html, self::URI);

        foreach($html->find('div.odad-card__wrapper') as $element) {
            $titletext = $element->find('p', 0)->innertext;
            $title = trim(explode('<br>', $titletext)[0]);
            $today = date('m/d/Y');
            $uri = self::URI . $element->find('div.js-odad-link', 1)->attr['data-link'];
            $item = [];
            $item['uri'] = $uri;
            $item['title'] = $title;
            $item['uid'] = $title;
            $item['timestamp'] = $today;
            $item['content'] = $element->find('p', 0)
            . '<br><a href="'
            . $uri
            . '"><img src="'
            . $element->find('div.js-odad-link', 1)->find('img', 0)->attr['src']
            . '" /></a>'
            ;

            $this->items[] = $item;
        }
    }
}
