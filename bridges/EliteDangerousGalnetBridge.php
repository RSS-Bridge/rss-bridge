<?php

class EliteDangerousGalnetBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Elite: Dangerous Galnet';
    const URI = 'https://community.elitedangerous.com/galnet/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Returns the latest page of news from Galnet';
    const PARAMETERS = [
        [
            'language' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'English' => 'en',
                    'French' => 'fr',
                    'German' => 'de'
                ],
                'defaultValue' => 'en'
            ]
        ]
    ];

    public function collectData()
    {
        $language = $this->getInput('language');
        $url = 'https://community.elitedangerous.com/';
        $url = $url . $language . '/galnet';
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('div.article') as $element) {
            $item = [];

            $uri = $element->find('h3 a', 0)->href;
            $uri = 'https://community.elitedangerous.com/' . $language . $uri;
            $item['uri'] = $uri;

            $item['title'] = $element->find('h3 a', 0)->plaintext;

            $content = $element->find('p', -1)->innertext;
            $item['content'] = $content;

            $date = $element->find('p.small', 0)->innertext;
            $article_year = substr($date, -4) - 1286; //Convert E:D date to actual date
            $date = substr($date, 0, -4) . $article_year;
            $item['timestamp'] = strtotime($date);

            $this->items[] = $item;
        }

        //Remove duplicates that sometimes show up on the website
        $this->items = array_unique($this->items, SORT_REGULAR);
    }
}
