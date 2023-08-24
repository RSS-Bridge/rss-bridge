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
                    'English' => 'en-US',
                    'French' => 'fr-FR',
                    'German' => 'de-DE',
                    'Russian' => 'ru-RU',
                    'Spanish' => 'es-ES',
                ],
                'defaultValue' => 'en-US'
            ]
        ]
    ];

    public function collectData()
    {
        $language = $this->getInput('language');
        $url = 'https://cms.zaonce.net/';
        $url = $url . $language . '/jsonapi/node/galnet_article';
        $url = $url . '?&sort=-published_at&page[offset]=0&page[limit]=12';

        $html = getSimpleHTMLDOM($url);
        $json = json_decode($html);

        foreach ($json->data as $element) {
            $item = [];

            $uri = 'https://www.elitedangerous.com/news/galnet/';
            $uri = $uri . $element->attributes->field_slug;
            $item['uri'] = $uri;

            $item['title'] = $element->attributes->title;

            $picture = 'https://hosting.zaonce.net/elite-dangerous/galnet/';
            $picture = $picture . $element->attributes->field_galnet_image . '.png';
            $picture = '<img src="' . $picture . '"/>';

            $content = $element->attributes->body->processed;
            $item['content'] = $picture . $content;

            $item['timestamp'] = strtotime($element->attributes->published_at);

            $this->items[] = $item;
        }

        //Remove duplicates that sometimes show up on the website
        $this->items = array_unique($this->items, SORT_REGULAR);
    }
}
