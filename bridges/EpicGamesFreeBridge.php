<?php

class EpicGamesFreeBridge extends BridgeAbstract
{
    const NAME = 'Epic Games Free Games';
    const MAINTAINER = 'phantop';
    const URI = 'https://store.epicgames.com/';
    const DESCRIPTION = 'Returns the latest free games from Epic Games';
    const PARAMETERS = [ [
        'locale' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'English' => 'en-US',
                'العربية' => 'ar',
                'Deutsch' => 'de',
                'Español (Spain)' => 'es-ES',
                'Español (LA)' => 'es-MX',
                'Français' => 'fr',
                'Italiano' => 'it',
                '日本語' => 'ja',
                '한국어' => 'ko',
                'Polski' => 'pl',
                'Português (Brasil)' => 'pt-BR',
                'Русский' => 'ru',
                'ไทย' => 'th',
                'Türkçe' => 'tr',
                '简体中文' => 'zh-CN',
                '繁體中文' => 'zh-Hant',
             ],
            'title' => 'Language for game information',
            'defaultValue' => 'en-US',
        ],
        'country' => [
            'name' => 'Country',
            'title' => 'Country store to check for deals',
            'defaultValue' => 'US',
        ]
    ]];

    public function collectData()
    {
        $url = 'https://store-site-backend-static.ak.epicgames.com/freeGamesPromotions?';
        $params = [
            'locale' => $this->getInput('locale'),
            'country' => $this->getInput('country'),
            'allowCountries' => $this->getInput('country'),
        ];
        $url .= http_build_query($params);
        $json = Json::decode(getContents($url));

        $data = $json['data']['Catalog']['searchStore']['elements'];
        foreach ($data as $element) {
            if (!isset($element['promotions']['promotionalOffers'][0])) {
                continue;
            }
            $item = [
                'author' => $element['seller']['name'],
                'content' => $element['description'],
                'enclosures' => array_map(fn($item) => $item['url'], $element['keyImages']),
                'timestamp' => strtotime($element['promotions']['promotionalOffers'][0]['promotionalOffers'][0]['startDate']),
                'title' => $element['title'],
                'url' => parent::getURI() . $this->getInput('locale') . '/p/' . $element['urlSlug'],
            ];
            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $uri = parent::getURI() . $this->getInput('locale') . '/free-games';
        return $uri;
    }
}
