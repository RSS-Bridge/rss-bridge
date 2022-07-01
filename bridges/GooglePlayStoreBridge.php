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

    const INFORMATION_MAP = [
        'Updated' => 'timestamp',
        'Current Version' => 'title',
        'Offered By' => 'author'
    ];

    public function collectData()
    {
        $appuri = static::URI . '/details?id=' . $this->getInput('id');
        $html = getSimpleHTMLDOM($appuri);

        $item = [];
        $item['uri'] = $appuri;
        $item['content'] = $html->find('div[itemprop=description]', 1)->innertext;

        // Find other fields from Additional Information section
        foreach ($html->find('.hAyfc') as $info) {
            $index = self::INFORMATION_MAP[$info->first_child()->plaintext] ?? null;
            if (is_null($index)) {
                continue;
            }
            $item[$index] = $info->children(1)->plaintext;
        }

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
