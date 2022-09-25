<?php

class DuckDuckGoBridge extends BridgeAbstract
{
    const MAINTAINER = 'Astalaseven';
    const NAME = 'DuckDuckGo';
    const URI = 'https://duckduckgo.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Returns results from DuckDuckGo.';

    const SORT_DATE = '+sort:date';
    const SORT_RELEVANCE = '';

    const PARAMETERS = [ [
        'u' => [
            'name' => 'keyword',
            'exampleValue' => 'duck',
            'required' => true
        ],
        'sort' => [
            'name' => 'sort by',
            'type' => 'list',
            'required' => false,
            'values' => [
                'date' => self::SORT_DATE,
                'relevance' => self::SORT_RELEVANCE
            ],
            'defaultValue' => self::SORT_DATE
        ]
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . 'html/?kd=-1&q=' . $this->getInput('u') . $this->getInput('sort'));

        foreach ($html->find('div.result') as $element) {
            $item = [];
            $item['uri'] = $element->find('a.result__a', 0)->href;
            $item['title'] = $element->find('h2.result__title', 0)->plaintext;
            $item['content'] = $element->find('a.result__snippet', 0)->plaintext;
            $this->items[] = $item;
        }
    }
}
