<?php

class NFLRUSBridge extends BridgeAbstract
{
    const NAME = 'NFLRUS';
    const URI = 'http://nflrus.ru/';
    const DESCRIPTION = 'Returns the recent articles published on nflrus.ru';
    const MAINTAINER = 'Maxim Shpak';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);
        $html = defaultLinkTo($html, self::URI);

        $articles = $html->find('.big-post_content-col');

        foreach ($articles as $article) {
            $item = [];

            $url = $article->find('.big-post_title.card-title a', 0);

            $item['uri'] = $url->href;
            $item['title'] = $url->plaintext;
            $item['content'] = $article->find('div', 0)->innertext;
            $this->items[] = $item;
        }
    }
}
