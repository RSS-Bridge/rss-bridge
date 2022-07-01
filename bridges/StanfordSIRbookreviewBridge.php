<?php

class StanfordSIRbookreviewBridge extends BridgeAbstract
{
    const MAINTAINER = 'Kidman1670';
    const NAME = 'StanfordSIRbookreviewBridge';
    const URI = 'https://ssir.org/books/';
    const CACHE_TIMEOUT = 21600;
    const DESCRIPTION = 'Return results from SSIR book review.';
    const PARAMETERS = [ [
             'style' => [
                'name' => 'style',
                'type' => 'list',
                'values' => [
                    'reviews' => 'reviews',
                    'excerpts' => 'excerpts',
                ]
             ]
        ]
    ];

    public function collectData()
    {
        switch ($this->getInput('style')) {
            case 'reviews':
                $url = self::URI . 'reviews';
                break;
            case 'excerpts':
                $url = self::URI . 'excerpts';
                break;
        }

        $html = getSimpleHTMLDOM($url)
            or returnServerError('Failed loading content!');
        foreach ($html->find('article') as $element) {
            $item = [];
            $item['title'] = $element->find('div > h4 > a', 0)->plaintext;
            $item['uri'] = $element->find('div > h4 > a', 0)->href;
            $item['content'] = $element->find('div > div.article-entry > p', 2)->plaintext;
            $item['author'] = $element->find('div > div > p', 0)->plaintext;
            $this->items[] = $item;
        }
    }
}
