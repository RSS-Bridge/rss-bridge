<?php

class WarhammerComBridge extends BridgeAbstract
{
    const NAME = 'Warhammer Community Blog';
    const URI = 'https://www.warhammer-community.com';
    const DESCRIPTION = 'Warhammer Community Blog';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400;

    public function collectData()
    {
        $url = static::URI . '/api/search/news/';

        $header = [
            'Content-Type: application/json',
        ];

        $data = '{"sortBy":"date_desc","category":"","collections":["articles"],"game_systems":[],"index":"news","locale":"en-gb","page":0,"perPage":16,"topics":[]}';

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ];

        $json = getContents($url, $header, $opts);
        $json = json_decode($json);

        foreach ($json->news as $article) {
            $articleurl = static::URI . $article->uri;

            $fullarticle = getSimpleHTMLDOMCached($articleurl);
            $content = $fullarticle->find('.article-content', 0);

            $categories = [];
            foreach ($article->topics as $topic) {
                $categories[] = $topic->title;
            }

            $this->items[] = [
                'title' => $article->title,
                'uri' => static::URI . $article->uri,
                'timestamp' => strtotime($article->date),
                'content' => $content,
                'uid' => $article->uuid,
                'categories' => $categories
            ];
        }
    }
}
