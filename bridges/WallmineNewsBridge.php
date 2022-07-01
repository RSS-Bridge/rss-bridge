<?php

class WallmineNewsBridge extends BridgeAbstract
{
    const NAME = 'Wallmine News Bridge';
    const URI = 'https://wallmine.com';
    const DESCRIPTION = 'Returns financial news';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 900; // 15 mins

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI() . '/news/');

        $html = defaultLinkTo($html, self::URI);

        foreach ($html->find('div.container.news-card') as $div) {
            $item = [];
            $item['uri'] = $div->find('a', 0)->href;

            $image = $div->find('img.img-fluid', 0)->src;

            $page = getSimpleHTMLDOMCached($item['uri'], 7200);

            $article = $page->find('div.container.article-container', 0);

            $item['title'] = $article->find('h1', 0)->plaintext;

            $article->find('p.published-on', 0)->children(0)->outertext = '';
            $article->find('p.published-on', 0)->children(1)->outertext = '';
            $date = str_replace('at', '', $article->find('p.published-on', 0)->innertext);

            $item['timestamp'] = $date;

            $article->find('h1', 0)->outertext = '';
            $article->find('p.published-on', 0)->outertext = '';

            $item['content'] = $article->innertext;
            $item['enclosures'][] = $image;

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }
}
