<?php

class MarktplaatsBridge extends BridgeAbstract
{
    const NAME = 'Marktplaats';
    const URI = 'https://marktplaats.nl';
    const DESCRIPTION = 'Read search queries from marktplaats.nl';
    const PARAMETERS = [
        'Search' => [
            'q' => [
                'name' => 'query',
                'type' => 'text',
                'exampleValue' => 'lamp',
                'required' => true,
                'title' => 'The search string for marktplaats',
            ],
            'z' => [
                'name' => 'zipcode',
                'type' => 'text',
                'required' => false,
                'exampleValue' => '1013AA',
                'title' => 'Zip code for location limited searches',
            ],
            'd' => [
                'name' => 'distance',
                'type' => 'number',
                'required' => false,
                'exampleValue' => '100000',
                'title' => 'The distance in meters from the zipcode',
            ],
            'f' => [
                'name' => 'priceFrom',
                'type' => 'number',
                'required' => false,
                'title' => 'The minimal price in cents',
            ],
            't' => [
                'name' => 'priceTo',
                'type' => 'number',
                'required' => false,
                'title' => 'The maximal price in cents',
            ],
            's' => [
                'name' => 'showGlobal',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Include result with negative distance',
            ],
            'i' => [
                'name' => 'includeImage',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Include the image at the end of the content',
            ],
            'r' => [
                'name' => 'includeRaw',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Include the raw data behind the content',
            ]
        ]
    ];
    const CACHE_TIMEOUT = 900;

    public function collectData()
    {
        $query = '';
        $excludeGlobal = false;
        if (!is_null($this->getInput('z')) && !is_null($this->getInput('d'))) {
            $query = '&postcode=' . $this->getInput('z') . '&distanceMeters=' . $this->getInput('d');
        }
        if (!is_null($this->getInput('f'))) {
            $query .= '&PriceCentsFrom=' . $this->getInput('f');
        }
        if (!is_null($this->getInput('t'))) {
            $query .= '&PriceCentsTo=' . $this->getInput('t');
        }
        if (!is_null($this->getInput('s'))) {
            if (!$this->getInput('s')) {
                $excludeGlobal = true;
            }
        }
        $url = 'https://www.marktplaats.nl/lrp/api/search?query=' . urlencode($this->getInput('q')) . $query;
        $jsonString = getSimpleHTMLDOM($url);
        $jsonObj = json_decode($jsonString);
        foreach ($jsonObj->listings as $listing) {
            if (!$excludeGlobal || $listing->location->distanceMeters >= 0) {
                $item = [];
                $item['uri'] = 'https://marktplaats.nl' . $listing->vipUrl;
                $item['title'] = $listing->title;
                $item['timestamp'] = $listing->date;
                $item['author'] = $listing->sellerInformation->sellerName;
                $item['content'] = $listing->description;
                $item['categories'] = $listing->verticals;
                $item['uid'] = $listing->itemId;
                if (!is_null($this->getInput('i')) && !empty($listing->imageUrls)) {
                    $item['enclosures'] = $listing->imageUrls;
                    if (is_array($listing->imageUrls)) {
                        foreach ($listing->imageUrls as $imgurl) {
                            $item['content'] .= "<br />\n<img src='https:" . $imgurl . "' />";
                        }
                    } else {
                        $item['content'] .= "<br>\n<img src='https:" . $listing->imageUrls . "' />";
                    }
                }
                if (!is_null($this->getInput('r'))) {
                    if ($this->getInput('r')) {
                        $item['content'] .= "<br />\n<br />\n<br />\n" . json_encode($listing);
                    }
                }
                $item['content'] .= "<br>\n<br>\nPrice: " . $listing->priceInfo->priceCents / 100;
                $item['content'] .= '&nbsp;&nbsp;(' . $listing->priceInfo->priceType . ')';
                if (!empty($listing->location->cityName)) {
                    $item['content'] .= "<br><br>\n" . $listing->location->cityName;
                }
                if (!is_null($this->getInput('r'))) {
                    if ($this->getInput('r')) {
                        $item['content'] .= "<br />\n<br />\n<br />\n" . json_encode($listing);
                    }
                }
                $this->items[] = $item;
            }
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('q'))) {
            return $this->getInput('q') . ' - Marktplaats';
        }
        return parent::getName();
    }
}
