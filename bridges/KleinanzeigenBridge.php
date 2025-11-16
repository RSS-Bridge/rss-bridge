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
                $html = getSimpleHTMLDOM($this->getURI() . '/s-bestandsliste.html?userId=' . $this->getInput('userid') . '&pageNum=' . $i . '&sortingField=SORTING_DATE');

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
                $searchUrl = $this->getURI() . '/s-suchanfrage.html?' . http_build_query([
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

        $item['content'] = '';

        $json = $element->find('.aditem-image > script', 0);
        if ($json) {
            $data = json_decode($json->innertext, true);
            $item['title'] = $data['title'];
            $item['content'] .= '<div><p>' . $data['description'] . '</div></p></br>';
        } else {
            $item['title'] = $element->find('h2', 0)->plaintext;
            $item['content'] .= $element->find('.aditem-main--middle--description');
        }

        if ($element->find('.aditem-main--top', 0)) {
            $item['content'] .= $element->find('.aditem-main--top', 0);
        }

        if ($element->find('.aditem-main--middle--price-shipping', 0)) {
            $item['content'] .= preg_replace(
                '#(<p\s+class="aditem-main--middle--price-shipping--old-price"[^>]*>.*?</p>)#si',
                '<s>$1</s>',
                $element->find('.aditem-main--middle--price-shipping', 0)
            );
        }

        if ($element->find('.aditem-main--bottom', 0)) {
            $item['content'] .= $element->find('.aditem-main--bottom', 0);
        }

        $item['content'] = sanitize($item['content']);

        $item['uid'] = $element->getAttribute('data-adid');
        $item['uri'] = urljoin($this->getURI(), $element->getAttribute('data-href'));

        $dateString = trim($element->find('div.aditem-main--top--right', 0)->plaintext);
        if ($dateString) {
                $dateString = str_ireplace(
                    ['Gestern', 'Heute'],
                    ['yesterday', 'today'],
                    $dateString
                );

                $item['timestamp'] = strtotime($dateString);
        } else {
            $item['timestamp']  = time();
        }

        if ($element->find('img', 0)) {
            //enhance img quality. Cannot use convertLazyLoading() here due to non-standard URI suffix in srcset.
            $item['enclosures'] = [preg_replace('/rule=\$_\d+\.AUTO/i', 'rule=$_57.AUTO', $element->find('img', 0)->getAttribute('src')) . '#.image'];
        };

        $this->items[] = $item;
    }

    private function findCategoryId()
    {
        if ($this->getInput('category')) {
            $html = getSimpleHTMLDOM($this->getURI() . '/s-kategorie-baum.html');
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
