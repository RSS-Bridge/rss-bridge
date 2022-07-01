<?php

class BastaBridge extends BridgeAbstract
{
    const MAINTAINER = 'qwertygc';
    const NAME = 'Bastamag Bridge';
    const URI = 'https://www.bastamag.net/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Returns the newest articles.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . 'spip.php?page=backend');

        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 10) {
                $item = [];
                $item['title'] = $element->find('title', 0)->innertext;
                $item['uri'] = $element->find('guid', 0)->plaintext;
                $item['timestamp'] = strtotime($element->find('dc:date', 0)->plaintext);

                $html = getSimpleHTMLDOM($item['uri']);
                $html = defaultLinkTo($html, self::URI);

                $item['content'] = $html->find('div.texte', 0)->innertext;
                $this->items[] = $item;
                $limit++;
            }
        }
    }
}
