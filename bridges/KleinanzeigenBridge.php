<?php

class KleinanzeigenBridge extends BridgeAbstract
{
    const MAINTAINER = 'knrdl';
    const NAME = 'Kleinanzeigen Bridge';
    const URI = 'https://www.kleinanzeigen.de';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = '(ebay) Kleinanzeigen';

    const PARAMETERS = [
        'By search' => [
            'query' => [
                'name' => 'query',
                'required' => false,
                'title' => 'query term',
            ],
            'category' => [
                'name' => 'category',
                'required' => false,
                'title' => 'search category, e.g. "Damenschuhe" or "Notebooks"'
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
                'title' => 'location radius in kilometers',
                'defaultValue' => 10,
            ],
            'minprice' => [
                'name' => 'minimum price',
                'required' => false,
                'type' => 'number',
                'title' => 'in euros'
            ],
            'maxprice' => [
                'name' => 'maximum price',
                'required' => false,
                'type' => 'number',
                'title' => 'in euros'
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
                return 'Kleinanzeigen ' . $this->getInput('query') . ' ' . $this->getInput('category') . ' ' . $this->getInput('location');
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
            $categoryId = $this->findCategoryId();
            for ($page = 1; $page <= $this->getInput('pages'); $page++) {
                $searchUrl = self::URI . '/s-suchanfrage.html?' . http_build_query([
                    'keywords' => $this->getInput('query'),
                    'locationStr' => $this->getInput('location'),
                    'locationId' => '',
                    'radius' => $this->getInput('radius') || '0',
                    'sortingField' => 'SORTING_DATE',
                    'categoryId' => $categoryId,
                    'pageNum' => $page,
                    'maxPrice' => $this->getInput('maxprice'),
                    'minPrice' => $this->getInput('minprice')
                ]);

                $html = getSimpleHTMLDOM($searchUrl);

                // end of list if returned page is not the expected one
                if ($html->find('.pagination-current', 0)->plaintext != $page) {
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

    private function findCategoryId()
    {
        if ($this->getInput('category')) {
            $html = getSimpleHTMLDOM(self::URI . '/s-kategorie-baum.html');
            foreach ($html->find('a[data-val]') as $element) {
                $catId = (int)$element->getAttribute('data-val');
                $catName = $element->plaintext;
                if (str_contains(strtolower($catName), strtolower($this->getInput('category')))) {
                    return $catId;
                }
            }
        }
        return 0;
    }
}
