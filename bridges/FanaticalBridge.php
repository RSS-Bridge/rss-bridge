<?php

class FanaticalBridge extends BridgeAbstract
{
    const NAME = 'Fanatical';
    const MAINTAINER = 'phantop';
    const URI = 'https://www.fanatical.com/en/';
    const DESCRIPTION = 'Returns bundles from Fanatical.';
    const PARAMETERS = [[
        'type' => [
            'name' => 'Bundle type',
            'type' => 'list',
            'defaultValue' => 'all',
            'values' => [
                'All' => 'all',
                'Books' => 'book-',
                'ELearning' => 'elearning-',
                'Games' => '',
                'Software' => 'software-',
            ]
        ]
    ]];


    const IMGURL = 'https://fanatical.imgix.net/product/original/';
    public function collectData()
    {
        $api = 'https://www.fanatical.com/api/all/en';
        $json = json_decode(getContents($api), true)['pickandmix'];
        $type = $this->getInput('type');

        foreach ($json as $element) {
            if ($type != 'all') {
                if ($element['type'] != $type . 'bundle') {
                    continue;
                }
            }

            $item = [
                'categories' => [$element['type']],
                'content' => '<ul>',
                'enclosures' => [self::IMGURL . $element['cover_image']],
                'timestamp' => $element['valid_from'],
                'title' => $element['name'],
                'uri' => parent::getURI() . 'pick-and-mix/' . $element['slug'],
            ];

            $slugs = [];
            foreach ($element['products'] as $product) {
                $slug = $product['slug'];
                if (in_array($slug, $slugs)) {
                    continue;
                }
                $slugs[] = $slug;
                $uri = parent::getURI() . 'game/' . $slug;
                $item['content'] .= '<li><a href="' . $uri . '">' . $product['name'] . '</a></li>';
                $item['enclosures'][] = self::IMGURL . $product['cover'];
            }
            foreach ($element['tiers'] as $tier) {
                $count = $tier['quantity'];
                $price = round($tier['price']['USD'] / 100, 2);
                $per = round($price / $count, 2);
                $item['categories'][] = "$count at $per for $price total";
            }

            $item['content'] .= '</ul>';
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        $name .= $this->getKey('type') ? ' - ' . $this->getKey('type') : '';
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI();
        $type = $this->getKey('type');
        if ($type) {
            $uri .= 'bundle/';
            if ($type != 'All') {
                $uri .= strtolower($type);
            }
        }
        return $uri;
    }

    public function getIcon()
    {
        return 'https://cdn.fanatical.com/production/icons/fanatical-icon-android-chrome-192x192.png';
    }
}
