<?php

class MotatosBridge extends BridgeAbstract
{
    const NAME = 'Motatos / Matsmart';
    const URI = 'https://www.motatos.de/neu-im-shop';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'New articles in the Motatos / Matsmart online shop';
    const MAINTAINER = 'knrdl';
    const PARAMETERS = [[
        'region' => [
            'name' => 'Region',
            'type' => 'list',
            'title' => 'Choose country',
            'values' => [
                'Austria' => 'at',
                'Denmark' => 'dk',
                'Finland' => 'fi',
                'Germany' => 'de',
                'Sweden' => 'se',
            ],
        ],
    ]];

    public function getName()
    {
        switch ($this->getInput('region')) {
            case 'at':
                return 'Motatos';
            case 'dk':
                return 'Motatos';
            case 'de':
                return 'Motatos';
            case 'fi':
                return 'Matsmart';
            case 'se':
                return 'Matsmart';
            default:
                return self::NAME;
        }
    }

    public function getURI()
    {
        switch ($this->getInput('region')) {
            case 'at':
                return 'https://www.motatos.at/neu-im-shop';
            case 'dk':
                return 'https://www.motatos.dk/nye-varer';
            case 'de':
                return 'https://www.motatos.de/neu-im-shop';
            case 'fi':
                return 'https://www.matsmart.fi/uusimmat';
            case 'se':
                return 'https://www.matsmart.se/nyinkommet';
            default:
                return self::URI;
        }
    }

    public function getIcon()
    {
        return 'https://www.motatos.de/favicon.ico';
    }

    private function getApiUrl()
    {
        switch ($this->getInput('region')) {
            case 'at':
                return 'https://api.findify.io/v4/4359f7b3-17e0-4f74-9fdb-e6606dfed25c/smart-collection/new-arrivals';
            case 'dk':
                return 'https://api.findify.io/v4/3709426e-621a-49df-bd61-ac8543452022/smart-collection/new-arrivals';
            case 'de':
                return 'https://api.findify.io/v4/2a044754-6cda-4541-b159-39133b75386c/smart-collection/new-arrivals';
            case 'fi':
                return 'https://api.findify.io/v4/63946f89-2a82-4839-a412-883b79144f7b/smart-collection/new-arrivals';
            case 'se':
                return 'https://api.findify.io/v4/3ae86b36-a1bd-4442-a3d9-2af6845908e6/smart-collection/new-arrivals';
        }
    }

    public function collectData()
    {
        // motatos uses this api to dynamically load more items on page scroll
        $json = getContents($this->getApiUrl() . '?t_client=0&user={%22uid%22:%220%22,%22sid%22:%220%22}');
        $jsonFile = json_decode($json, true);

        foreach ($jsonFile['items'] as $entry) {
            $item = [];
            $item['uid'] = $entry['custom_fields']['uuid'][0];
            $item['uri'] = $entry['product_url'];
            $item['timestamp'] = $entry['created_at'] / 1000;
            $item['title'] = $entry['title'];
            $item['content'] = <<<HTML
            <h1>{$entry['title']}</h1>
            <img src="{$entry['image_url']}" />
            <p>{$entry['price'][0]}€</p>
            HTML;
            $this->items[] = $item;
        }
    }
}
