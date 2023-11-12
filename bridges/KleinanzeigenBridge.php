<?php

class KleinanzeigenBridge extends BridgeAbstract
{
    const MAINTAINER = 'knrdl';
    const NAME = 'Kleinanzeigen Bridge';
    const URI = 'https://www.kleinanzeigen.de';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'ebay Kleinanzeigen';

    const PARAMETERS = [
        'By search' => [
            'query' => [
                'name' => 'query',
                'required' => false,
                'title' => 'query term',
            ],
            'location' => [
                'name' => 'location',
                'required' => false,
                'title' => 'e.g. Berlin',
            ],
            'radius' => [
                'name' => 'radius',
                'required' => false,
                'type' => 'number',
                'title' => 'search radius in kilometers',
                'defaultValue' => 10,
            ],
            'pages' => [
                'name' => 'pages',
                'required' => true,
                'type' => 'number',
                'title' => 'how many pages to fetch',
                'defaultValue' => 2,
            ]
        ],
        'By profile' => [
            'userid' => [
                'name' => 'user id',
                'required' => true,
                'type' => 'number',
                'exampleValue' => 12345678
            ],
            'pages' => [
                'name' => 'pages',
                'required' => true,
                'type' => 'number',
                'title' => 'how many pages to fetch',
                'defaultValue' => 2,
            ]
        ],
    ];

    public function getIcon()
    {
        return 'https://www.kleinanzeigen.de/favicon.ico';
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By profile':
                return 'Kleinanzeigen Profil';
            case 'By search':
                return 'Kleinanzeigen ' . $this->getInput('query') . ' / ' . $this->getInput('location');
            default:
                return parent::getName();
        }
    }

    public function collectData()
    {
        if ($this->queriedContext === 'By profile') {
            for ($i = 1; $i <= $this->getInput('pages'); $i++) {
                $html = getSimpleHTMLDOM(self::URI . '/s-bestandsliste.html?userId=' . $this->getInput('userid') . '&pageNum=' . $i . '&sortingField=SORTING_DATE');

                $foundItem = false;
                foreach ($html->find('article.aditem') as $element) {
                    $this->addItem($element);
                    $foundItem = true;
                }
                if (!$foundItem) {
                    break;
                }
            }
        }

        if ($this->queriedContext === 'By search') {
            $locationID = '';
            if ($this->getInput('location')) {
                $json = getContents(self::URI . '/s-ort-empfehlungen.json?' . http_build_query(['query' => $this->getInput('location')]));
                $jsonFile = json_decode($json, true);
                $locationID = str_replace('_', '', array_key_first($jsonFile));
            }
            for ($i = 1; $i <= $this->getInput('pages'); $i++) {
                $searchUrl = self::URI . '/s-walled-garden/';
                if ($i != 1) {
                    $searchUrl .= 'seite:' . $i . '/';
                }
                if ($this->getInput('query')) {
                    $searchUrl .= urlencode($this->getInput('query')) . '/k0';
                }
                if ($locationID) {
                    $searchUrl .= 'l' . $locationID;
                }
                if ($this->getInput('radius')) {
                    $searchUrl .= 'r' . $this->getInput('radius');
                }

                $html = getSimpleHTMLDOM($searchUrl);

                // end of list if returned page is not the expected one
                if ($html->find('.pagination-current', 0)->plaintext != $i) {
                    break;
                }

                foreach ($html->find('ul#srchrslt-adtable article.aditem') as $element) {
                    $this->addItem($element);
                }
            }
        }
    }

    private function addItem($element)
    {
        $item = [];

        $item['uid'] = $element->getAttribute('data-adid');
        $item['uri'] = self::URI . $element->getAttribute('data-href');

        $item['title'] = $element->find('h2', 0)->plaintext;
        $item['timestamp'] = $element->find('div.aditem-main--top--right', 0)->plaintext;
        $imgUrl = str_replace(
            'rule=$_2.JPG',
            'rule=$_57.JPG',
            str_replace(
                'rule=$_35.JPG',
                'rule=$_57.JPG',
                $element->find('img', 0) ? $element->find('img', 0)->getAttribute('src') : ''
            )
        ); //enhance img quality
        $textContainer = $element->find('div.aditem-main', 0);
        $textContainer->find('a', 0)->href = self::URI . $textContainer->find('a', 0)->href; // add domain to url
        $item['content'] = '<img src="' . $imgUrl . '"/>' .
        $textContainer->outertext;

        $this->items[] = $item;
    }
}
