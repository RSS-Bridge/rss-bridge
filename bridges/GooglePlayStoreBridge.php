<?php

class GooglePlayStoreBridge extends BridgeAbstract
{
    const NAME = 'Google Play Store';
    const URI = 'https://play.google.com/store/apps';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the most recent version of an app with its changelog';

    const TEST_DETECT_PARAMETERS = [
        'https://play.google.com/store/apps/details?id=com.ichi2.anki' => [
            'id' => 'com.ichi2.anki'
        ]
    ];

    const PARAMETERS = [[
        'id' => [
            'name' => 'Application ID',
            'exampleValue' => 'com.ichi2.anki',
            'required' => true
        ]
    ]];

    public function collectData()
    {
        $id = $this->getInput('id');
        $url = 'https://play.google.com/store/apps/details?id=' . $id;
        $html = getSimpleHTMLDOM($url);

        $updatedAtElement = $html->find('div.TKjAsc div', 2);
        // Updated onSep 27, 2023
        $updatedAt = $updatedAtElement->plaintext;
        $description = $html->find('div.bARER', 0);

        $item = [];
        $item['uri'] = $url;
        $item['title'] = $id . ' ' . $updatedAt;
        $item['content'] = $description->innertext ?? '';
        $item['uid'] = 'GooglePlayStoreBridge/' . $updatedAt;
        $this->items[] = $item;
    }

    public function detectParameters($url)
    {
        // Example: https://play.google.com/store/apps/details?id=com.ichi2.anki

        $params = [];
        $regex = '/^(https?:\/\/)?play\.google\.com\/store\/apps\/details\?id=([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['id'] = urldecode($matches[2]);
            return $params;
        }

        return null;
    }
}
