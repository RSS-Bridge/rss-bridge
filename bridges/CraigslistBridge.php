<?php

class CraigslistBridge extends BridgeAbstract
{
    const NAME = 'Craigslist Bridge';
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

        // Limit the number of posts
        if ($this->getInput('limit') > 0) {
            $results = array_slice($results, 0, $this->getInput('limit'));
        }

        foreach ($results as $post) {
            $item = [];

            $itemUri = $post->find('a', 0)->href;

            $item['uri'] = $itemUri;
            $item['title'] = $post->getAttribute('title');
            $item['uid'] = $itemUri;

            $price = $post->find('.price', 0)->plaintext ?? '';
            $location = $post->find('.location', 0)->plaintext ?? '';
            $item['content'] = sprintf('%s %s', $price, $location);

            $images = $this->getImages($itemUri);
            if (!empty($images)) {
                $item['content'] .= '<br>';
                foreach ($images as $image) {
                    $imageUri = $image->src;
                    $item['content'] .= '<img src="' . $imageUri . '">';
                    $item['enclosures'][] = $imageUri;
                }
            }
            $this->items[] = $item;
        }
    }

    private function getImages($postUrl): array
    {
        $html = getSimpleHTMLDOM($postUrl);

        // Try to extract imgList from the page's scripts
        $imgList = [];
        foreach ($html->find('script') as $script) {
            if (preg_match('/var imgList = (\[.*?\]);/s', $script->innertext, $matches)) {
                $json = $matches[1];
                $imgList = json_decode($json, true);
                break;
            }
        }

        $images = [];
        if (!empty($imgList)) {
            foreach ($imgList as $img) {
                if (isset($img['url'])) {
                    $image = new stdClass();
                    $image->src = $img['url'];
                    $images[] = $image;
                }
            }
        } else {
            // Fallback to DOM search if imgList is not found
            $images = $html->find('.swipe-wrap img') ?? [];
        }

        return $images;
    }
}
