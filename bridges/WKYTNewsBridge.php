<?php

class WKYTNewsBridge extends BridgeAbstract
{
    const NAME = 'WKYT Lexington News';
    const URI = 'https://www.wkyt.com/news/';
    const DESCRIPTION = 'Returns the recent articles published on WKYT News (Lexington KY)';
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
