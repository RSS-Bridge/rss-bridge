<?php

class WYMTNewsBridge extends BridgeAbstract
{
    const NAME = 'WYMT Mountain News';
    const URI = 'https://www.wymt.com/news/';
    const DESCRIPTION = 'Returns the recent articles published on WYMT Mountain News (Hazard KY)';
    const MAINTAINER = 'mattconnell';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);
        $html = defaultLinkTo($html, self::URI);

        $articles = $html->find('.card-body');

        foreach ($articles as $article) {
            $item = [];
            $url = $article->find('.headline a', 0);
            $item['uri'] = $url->href;
            $item['title'] = trim($url->plaintext);
            $item['author'] = $article->find('.author', 0)->plaintext;
            $item['content'] = $article->find('.deck', 0)->plaintext;
            $this->items[] = $item;
        }
    }
}
