<?php

class AmazonBridge extends BridgeAbstract
{
    const MAINTAINER = 'Alexis CHEMEL';
    const NAME = 'Amazon';
    const URI = 'https://www.amazon.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns products from Amazon search';

    const PARAMETERS = [[
        'q' => [
            'name' => 'Keyword',
            'required' => true,
            'exampleValue' => 'watch',
        ],
        'sort' => [
            'name' => 'Sort by',
            'type' => 'list',
            'values' => [
                'Relevance' => 'relevanceblender',
                'Price: Low to High' => 'price-asc-rank',
                'Price: High to Low' => 'price-desc-rank',
                'Average Customer Review' => 'review-rank',
                'Newest Arrivals' => 'date-desc-rank',
            ],
            'defaultValue' => 'relevanceblender',
        ],
        'tld' => [
            'name' => 'Country',
            'type' => 'list',
            'values' => [
                'Australia' => 'com.au',
                'Brazil' => 'com.br',
                'Canada' => 'ca',
                'China' => 'cn',
                'France' => 'fr',
                'Germany' => 'de',
                'India' => 'in',
                'Italy' => 'it',
                'Japan' => 'co.jp',
                'Mexico' => 'com.mx',
                'Netherlands' => 'nl',
                'Poland' => 'pl',
                'Spain' => 'es',
                'Sweden' => 'se',
                'Turkey' => 'com.tr',
                'United Kingdom' => 'co.uk',
                'United States' => 'com',
            ],
            'defaultValue' => 'com',
        ],
    ]];

    public function collectData()
    {
        $baseUrl = sprintf('https://www.amazon.%s', $this->getInput('tld'));

        $url = sprintf(
            '%s/s/?field-keywords=%s&sort=%s',
            $baseUrl,
            urlencode($this->getInput('q')),
            $this->getInput('sort')
        );

        $dom = getSimpleHTMLDOM($url);

        $elements = $dom->find('div.s-result-item');

        foreach ($elements as $element) {
            $item = [];

            $title = $element->find('h2', 0);
            if (!$title) {
                continue;
            }

            $item['title'] = $title->innertext;

            $itemUrl = $element->find('a', 0)->href;
            $item['uri'] = urljoin($baseUrl, $itemUrl);

            $image = $element->find('img', 0);
            if ($image) {
                $item['content'] = '<img src="' . $image->getAttribute('src') . '" /><br />';
            }

            $price = $element->find('span.a-price > .a-offscreen', 0);
            if ($price) {
                $item['content'] .= $price->innertext;
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('tld')) && !is_null($this->getInput('q'))) {
            return 'Amazon.' . $this->getInput('tld') . ': ' . $this->getInput('q');
        }

        return parent::getName();
    }
}
