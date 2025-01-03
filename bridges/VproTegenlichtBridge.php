<?php

class VproTegenlichtBridge extends BridgeAbstract
{
    const MAINTAINER = 'vincentvd';
    const NAME = 'VPRO tegenlicht';
    const URI = 'https://www.vpro.nl/programmas/tegenlicht/lees/artikelen.html';
    const CACHE_TIMEOUT = 900; // 15 minutes
    const DESCRIPTION = 'RSS feed for the VPRO tegenlicht website';

    public function getIcon()
    {
        return 'https://www.vpro.nl/.resources/vpro/favicons/vpro/favicon.ico';
    }

    public function collectData()
    {
        $url = sprintf('https://www.vpro.nl/programmas/tegenlicht/lees/artikelen.html');
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('ul#browsable-news-overview', 0);
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('li') as $article) {
            $a = $article->find('a.complex-teaser', 0);
            $title = $article->find('a.complex-teaser', 0)->title;
            $url = $article->find('a.complex-teaser', 0)->href;
            $author = 'VPRO tegenlicht';
            $content = $article->find('p.complex-teaser-summary', 0)->plaintext;
            $timestamp = strtotime($article->find('div.complex-teaser-data', 0)->plaintext);

            $item = [
                'uri' => $url,
                'author' => $author,
                'title' => $title,
                'timestamp' => $timestamp,
                'content' => $content
            ];

            $this->items[] = $item;
        }
    }
}
