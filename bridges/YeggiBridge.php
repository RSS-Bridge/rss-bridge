<?php

class YeggiBridge extends BridgeAbstract
{
    const NAME = 'Yeggi Search';
    const URI = 'https://www.yeggi.com';
    const DESCRIPTION = 'Returns 3D Models from Thingiverse, MyMiniFactory, Cults3D, and more';
    const MAINTAINER = 'AntoineTurmel';
    const PARAMETERS = [
        [
            'query' => [
                'name' => 'Search query',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert your search term here',
                'exampleValue' => 'vase'
            ],
            'sortby' => [
                'name' => 'Sort by',
                'type' => 'list',
                'required' => false,
                'values' => [
                    'Best match' => '0',
                    'Popular' => '1',
                    'Latest' => '2',
                ],
                'defaultValue' => 'newest'
            ],
            'show' => [
                'name' => 'Show',
                'type' => 'list',
                'required' => false,
                'values' => [
                    'All' => '0',
                    'Free' => '1',
                    'For sale' => '2',
                ],
                'defaultValue' => 'all'
            ],
            'showimage' => [
                'name' => 'Show image in content',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Activate to show the image in the content',
                'defaultValue' => 'checked'
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $results = $html->find('div.item_1_A');

        foreach ($results as $result) {
            $item = [];
            $title = $result->find('.item_3_B_2', 0)->plaintext;
            $explodeTitle = explode('&nbsp;  ', $title);
            if (count($explodeTitle) == 2) {
                $item['title'] = $explodeTitle[1];
            } else {
                $item['title'] = $explodeTitle[0];
            }
            $item['uri'] = self::URI . $result->find('a', 0)->href;
            $item['author'] = 'Yeggi';

            $text = $result->find('i');
            $item['content'] = $text[0]->plaintext . ' on ' . $text[1]->plaintext;
            $item['uid'] = hash('md5', $item['title']);

            foreach ($result->find('.item_3_B_2 > a[href^=/q/]') as $tag) {
                $item['tags'][] = $tag->plaintext;
            }

            $image = $result->find('img', 0)->src;

            if ($this->getInput('showimage')) {
                $item['content'] .= '<br><img src="' . $image . '">';
            }

            $item['enclosures'] = [$image];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('query'))) {
            $uri = self::URI . '/q/' . urlencode($this->getInput('query')) . '/';
            $uri .= '?o_f=' . $this->getInput('show');
            $uri .= '&o_s=' . $this->getInput('sortby');

            return $uri;
        }

        return parent::getURI();
    }
}
