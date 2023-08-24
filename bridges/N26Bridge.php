<?php

class N26Bridge extends BridgeAbstract
{
    const MAINTAINER = 'quentinus95';
    const NAME = 'N26 Blog';
    const URI = 'https://n26.com';
    const CACHE_TIMEOUT = 1800;
    const DESCRIPTION = 'Returns recent blog posts from N26.';

    public function collectData()
    {
        $limit = 5;
        $url = 'https://n26.com/en-eu/blog/all';
        $html = getSimpleHTMLDOM($url);

        $articles = $html->find('div[class="bl bm"]');

        foreach ($articles as $article) {
            $item = [];

            $itemUrl = self::URI . $article->find('a', 1)->href;
            $item['uri'] = $itemUrl;

            $item['title'] = $article->find('a', 1)->plaintext;

            $fullArticle = getSimpleHTMLDOM($item['uri']);

            $createdAt = $fullArticle->find('time', 0);
            $item['timestamp'] = strtotime($createdAt->plaintext);

            $this->items[] = $item;
            if (count($this->items) >= $limit) {
                break;
            }
        }
    }

    public function getIcon()
    {
        return 'https://n26.com/favicon.ico';
    }
}
