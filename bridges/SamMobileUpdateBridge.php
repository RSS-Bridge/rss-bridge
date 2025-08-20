<?php

declare(strict_types=1);

class SamMobileUpdateBridge extends BridgeAbstract
{
    const NAME = 'SamMobile updates';
    // pull info from this site
    const URI = 'https://www.sammobile.com/samsung/security/';
    const DESCRIPTION = 'Fetches the latest security patches for Samsung devices';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        [
            'model' => [
                'name' => 'Model',
                'exampleValue' => 'SM-S926B',
                'required' => true,
            ],
            'country' => [
                'name' => 'Country',
                'exampleValue' => 'EUX',
                'required' => true,
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    public function collectData()
    {
        $model = $this->getInput('model');
        $country = $this->getInput('country');
        $uri = self::URI . $model . '/' . $country;
        $html = getSimpleHTMLDOM($uri);

        $elementsDom = $html->find('.main-content-item__content.main-content-item__content-md table tbody tr');

        foreach ($elementsDom as $elementDom) {
            $item = [];

            $td = $elementDom->find('td');

            $title = 'Security patch: ' . $td[2] . ' - Android version: ' . $td[3] . ' - PDA: ' . $td[4];
            $text = 'Model: ' . $td[0] . '<br>Country/Carrier: ' . $td[1] . '<br>Security patch: ' . $td[2] . '<br>OS version: Android ' . $td[3] . '<br>PDA: ' . $td[4];

            $item['uri'] = $uri;
            $item['title'] = $title;
            $item['author'] = self::MAINTAINER;
            $item['timestamp'] = (new DateTime($td[2]->innertext))->getTimestamp();
            $item['content'] = $text;
            $item['uid'] = hash('sha256', $item['title']);

            $this->items[] = $item;
        }
    }
}
