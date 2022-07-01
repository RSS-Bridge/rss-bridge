<?php

class EtsyBridge extends BridgeAbstract
{
    const NAME = 'Etsy search';
    const URI = 'https://www.etsy.com';
    const DESCRIPTION = 'Returns feeds for search results';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        [
            'query' => [
                'name' => 'Search query',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert your search term here',
                'exampleValue' => 'lamp'
            ],
            'queryextension' => [
                'name' => 'Query extension',
                'type' => 'text',
                'required' => false,
                'title' => 'Insert additional query parts here
(anything after ?search=<your search query>)',
                'exampleValue' => '&explicit=1&locationQuery=2921044'
            ],
            'hideimage' => [
                'name' => 'Hide image in content',
                'type' => 'checkbox',
                'title' => 'Activate to hide the image in the content',
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $results = $html->find('li.wt-list-unstyled');

        foreach ($results as $result) {
            // Remove Lazy loading
            if ($result->find('.wt-skeleton-ui', 0)) {
                continue;
            }

            $item = [];

            $item['title'] = $result->find('a', 0)->title;
            $item['uri'] = $result->find('a', 0)->href;
            $item['author'] = $result->find('p.wt-text-gray > span', 2)->plaintext;

            $item['content'] = '<p>'
            . $result->find('span.currency-symbol', 0)->plaintext
            . $result->find('span.currency-value', 0)->plaintext
            . '</p><p>'
            . $result->find('a', 0)->title
            . '</p>';

            $image = $result->find('img.wt-display-block', 0)->src;

            if (!$this->getInput('hideimage')) {
                $item['content'] .= '<img src="' . $image . '">';
            }

            $item['enclosures'] = [$image];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('query'))) {
            $uri = self::URI . '/search?q=' . urlencode($this->getInput('query'));

            if (!is_null($this->getInput('queryextension'))) {
                $uri .= $this->getInput('queryextension');
            }

            return $uri;
        }

        return parent::getURI();
    }
}
