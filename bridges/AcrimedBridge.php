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
        $this->collectExpandableDatas(
            static::URI . 'spip.php?page=backend',
            $this->getInput('limit')
        );
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);

        $articlePage = getSimpleHTMLDOM($newsItem->link);
        $article = sanitize($articlePage->find('article.article1', 0)->innertext);
        $article = defaultLinkTo($article, static::URI);
        $item['content'] = $article;

        return $item;
    }
}
