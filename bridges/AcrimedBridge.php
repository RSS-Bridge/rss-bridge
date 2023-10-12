<?php

class AcrimedBridge extends FeedExpander
{
    const MAINTAINER = 'qwertygc';
    const NAME = 'Acrimed Bridge';
    const URI = 'https://www.acrimed.org/';
    const CACHE_TIMEOUT = 4800; //2hours
    const DESCRIPTION = 'Returns the newest articles';

    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'limit',
                'type' => 'number',
                'defaultValue' => -1,
            ]
        ]
    ];

    public function collectData()
    {
        $url = 'https://www.acrimed.org/spip.php?page=backend';
        $limit = $this->getInput('limit');
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem(array $item)
    {
        $articlePage = getSimpleHTMLDOM($item['uri']);
        $article = sanitize($articlePage->find('article.article1', 0)->innertext);
        $article = defaultLinkTo($article, static::URI);
        $item['content'] = $article;

        return $item;
    }
}
