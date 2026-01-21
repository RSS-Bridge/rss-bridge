<?php

class CraigslistBridge extends BridgeAbstract
{
    const NAME = 'Craigslist';
    const URI = 'https://craigslist.org/';
    const DESCRIPTION = 'Returns craigslist search results';

    const PARAMETERS = [
        [
            'region' => [
                'name' => 'Region',
                'title' => 'The subdomain before craigslist.org in the URL',
                'exampleValue' => 'sfbay',
                'required' => true
            ],
            'search' => [
                'name' => 'Search Query',
                'title' => 'Everything in the URL after /search/',
                'exampleValue' => 'sya?query=laptop',
                'required' => true
            ],
            'limit' => [
                'name' => 'Number of Posts',
                'type' => 'number',
                'title' => 'The maximum number of posts is 120. Use 0 for unlimited posts.',
                'defaultValue' => '25'
            ]
        ]
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://sfbay.craigslist.org/search/sya?query=laptop' => [
            'region' => 'sfbay',
            'search' => 'sya?query=laptop'
        ],
        'https://newyork.craigslist.org/search/sss?query=32gb+flash+drive&bundleDuplicates=1&max_price=20' => [
            'region' => 'newyork',
            'search' => 'sss?query=32gb+flash+drive&bundleDuplicates=1&max_price=20'
        ],
    ];

    const URL_REGEX = '/^https:\/\/(?<region>\w+).craigslist.org\/search\/(?<search>.+)/';

    public function detectParameters($url)
    {
        if (preg_match(self::URL_REGEX, $url, $matches)) {
            $params = [];
            $params['region'] = $matches['region'];
            $params['search'] = $matches['search'];
            return $params;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('region'))) {
            $domain = 'https://' . $this->getInput('region') . '.craigslist.org/search/';
            return urljoin($domain, $this->getInput('search'));
        }
        return parent::getURI();
    }

    public function collectData()
    {
        $uri = $this->getURI();
        $html = getSimpleHTMLDOM($uri);

        $results = $html->find('.cl-static-search-result');
        $queryResultsImages = $this->getQueryResultsImages($html);

        // Limit the number of posts
        if ($this->getInput('limit') > 0) {
            $results = array_slice($results, 0, $this->getInput('limit'));
        }

        $i = 0;
        foreach ($results as $post) {
            $item = [];

            $itemUri = $post->find('a', 0)->href;

            $item['uri'] = $itemUri;
            $item['title'] = $post->getAttribute('title');
            $item['uid'] = $itemUri;

            $price = $post->find('.price', 0)->plaintext ?? '';
            $location = $post->find('.location', 0)->plaintext ?? '';
            $item['content'] = sprintf('%s %s', $price, $location);

            $images = $queryResultsImages[$i] ?? [];
            if (!empty($images)) {
                $item['content'] .= '<br>';
                foreach ($images as $imageUrl) {
                    $item['content'] .= '<img src="' . $imageUrl . '">';
                    $item['enclosures'][] = $imageUrl;
                }
            }

            $i++;
            $this->items[] = $item;
        }
    }

    private function getQueryResultsImages($html): array
    {
        $images = [];

        // Find the JSON-LD script tag containing search results
        $jsonLdScript = $html->find('script#ld_searchpage_results', 0);

        if ($jsonLdScript) {
            $jsonContent = trim($jsonLdScript->innertext);
            $jsonData = json_decode($jsonContent);

            if (isset($jsonData->itemListElement) && is_array($jsonData->itemListElement)) {
                foreach ($jsonData->itemListElement as $item) {
                    if (isset($item->item->image) && is_array($item->item->image) && isset($item->position)) {
                        $productImages = [];
                        foreach ($item->item->image as $imageUrl) {
                            $productImages[] = $imageUrl;
                        }
                        if (!empty($productImages)) {
                            $images[$item->position] = $productImages;
                        }
                    }
                }
            }
        }

        return $images;
    }
}