<?php

class Formula1Bridge extends BridgeAbstract
{
    const NAME = 'Formula1 Bridge';
    const URI = 'https://formula1.com/';
    const DESCRIPTION = 'Returns latest official Formula 1 news';
    const MAINTAINER = 'AxorPL';

    const API_KEY = 'qPgPPRJyGCIPxFT3el4MF7thXHyJCzAP';
    const API_URL = 'https://api.formula1.com/v1/editorial/articles?limit=%u';

    const ARTICLE_AUTHOR = 'Formula 1';
    const ARTICLE_HTML = '<p>%s</p><a href="%s" target="_blank"><img src="%s" alt="%s" title="%s"></a>';
    const ARTICLE_URL = 'https://formula1.com/en/latest/article.%s.%s.html';

    const LIMIT_MIN = 1;
    const LIMIT_DEFAULT = 10;
    const LIMIT_MAX = 100;

    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Number of articles to return',
                'exampleValue' => self::LIMIT_DEFAULT,
                'defaultValue' => self::LIMIT_DEFAULT
            ]
        ]
    ];

    public function collectData()
    {
        $limit = $this->getInput('limit') ?: self::LIMIT_DEFAULT;
        $limit = min(self::LIMIT_MAX, max(self::LIMIT_MIN, $limit));
        $url = sprintf(self::API_URL, $limit);

        $json = json_decode(getContents($url, ['apikey: ' . self::API_KEY]));
        if (property_exists($json, 'error')) {
            returnServerError($json->message);
        }
        $list = $json->items;

        foreach ($list as $article) {
            if (property_exists($article->thumbnail, 'caption')) {
                $caption = $article->thumbnail->caption;
            } else {
                $caption = $article->thumbnail->image->title;
            }

            $item = [];
            $item['uri'] = sprintf(self::ARTICLE_URL, $article->slug, $article->id);
            $item['title'] = $article->title;
            $item['timestamp'] = $article->updatedAt;
            $item['author'] = self::ARTICLE_AUTHOR;
            $item['enclosures'] = [$article->thumbnail->image->url];
            $item['uid'] = $article->id;
            $item['content'] = sprintf(
                self::ARTICLE_HTML,
                $article->metaDescription,
                $item['uri'],
                $item['enclosures'][0],
                $caption,
                $caption
            );
            $this->items[] = $item;
        }
    }
}
