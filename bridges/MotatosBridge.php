<?php

class MotatosBridge extends BridgeAbstract
{
    const NAME = 'Motatos';
    const URI = 'https://www.motatos.de/neu-im-shop';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Neue Artikel im Motatos Online Shop';
    const MAINTAINER = 'knrdl';
    const PARAMETERS = [[
    ]];

    public function getIcon()
    {
        return 'https://www.motatos.de/favicon.ico';
    }

    public function collectData()
    {
        // motatos uses this api to dynamically load more items on page scroll
        $json = getContents('https://api.findify.io/v4/2a044754-6cda-4541-b159-39133b75386c/smart-collection/new-arrivals?t_client=' . time() . '&user={%22uid%22:%220%22,%22sid%22:%220%22}');
        $jsonFile = json_decode($json, true);

        foreach ($jsonFile['items'] as $entry) {
            $item = [];
            $item['uid'] = $entry['custom_fields']['uuid'];
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
