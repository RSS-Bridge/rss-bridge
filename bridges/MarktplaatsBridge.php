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
            'c' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Select a category' => '',
                    'Antiek en Kunst' => '1',
                    'Audio, Tv en Foto' => '31',
                    'Auto&#x27;s' => '91',
                    'Auto-onderdelen' => '2600',
                    'Auto diversen' => '48',
                    'Boeken' => '201',
                    'Caravans en Kamperen' => '289',
                    'Cd&#x27;s en Dvd&#x27;s' => '1744',
                    'Computers en Software' => '322',
                    'Contacten en Berichten' => '378',
                    'Diensten en Vakmensen' => '1098',
                    'Dieren en Toebehoren' => '395',
                    'Doe-het-zelf en Verbouw' => '239',
                    'Fietsen en Brommers' => '445',
                    'Hobby en Vrije tijd' => '1099',
                    'Huis en Inrichting' => '504',
                    'Huizen en Kamers' => '1032',
                    'Kinderen en Baby&#x27;s' => '565',
                    'Kleding | Dames' => '621',
                    'Kleding | Heren' => '1776',
                    'Motoren' => '678',
                    'Muziek en Instrumenten' => '728',
                    'Postzegels en Munten' => '1784',
                    'Sieraden, Tassen en Uiterlijk' => '1826',
                    'Spelcomputers en Games' => '356',
                    'Sport en Fitness' => '784',
                    'Telecommunicatie' => '820',
                    'Tickets en Kaartjes' => '1984',
                    'Tuin en Terras' => '1847',
                    'Vacatures' => '167',
                    'Vakantie' => '856',
                    'Verzamelen' => '895',
                    'Watersport en Boten' => '976',
                    'Witgoed en Apparatuur' => '537',
                    'Zakelijke goederen' => '1085',
                    'Diversen' => '428',
                ],
                'required' => false,
                'title' => 'The category to search in',
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
                'exampleValue' => '10000',
                'title' => 'The distance in meters from the zipcode',
            ],
            'f' => [
                'name' => 'priceFrom',
                'type' => 'number',
                'required' => false,
                'title' => 'The minimal price in euros',
            ],
            't' => [
                'name' => 'priceTo',
                'type' => 'number',
                'required' => false,
                'title' => 'The maximal price in euros',
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
            ],
            'sc' => [
                'name' => 'Sub category',
                'type' => 'number',
                'required' => false,
                'exampleValue' => '12345',
                'title' => 'Sub category has to be given by id as the list is too big to show here. 
                            Only use subcategories that belong to the main category. Both have to be correct',
            ],
        ]
    ];
    const CACHE_TIMEOUT = 900;

    public function collectData()
    {
        // Bouw de basis URL
        $url = 'https://www.marktplaats.nl/lrp/api/search';
        
        // Bouw de parameters
        $params = [
            'query' => $this->getInput('q'),
            'limit' => 100,
            'offset' => 0,
            'viewOptions' => 'list-view'
        ];
        
        // Voeg categorie toe
        if (!empty($this->getInput('c'))) {
            $params['categoryId'] = (int)$this->getInput('c');
        }
        
        // Voeg subcategorie toe
        if (!empty($this->getInput('sc'))) {
            $params['subCategoryId'] = (int)$this->getInput('sc');
        }
        
        // Locatie parameters - test eerst zonder geavanceerde filters
        if (!empty($this->getInput('z'))) {
            $params['postcode'] = $this->getInput('z');
        }
        
        if (!empty($this->getInput('d'))) {
            $params['distance'] = (int)$this->getInput('d');
        }
        
        // Prijs filters - als attributen
        $attributes = [];
        if (!empty($this->getInput('f')) || !empty($this->getInput('t'))) {
            $priceRange = [];
            if (!empty($this->getInput('f'))) {
                $priceRange['min'] = (int)($this->getInput('f') * 100);
            }
            if (!empty($this->getInput('t'))) {
                $priceRange['max'] = (int)($this->getInput('t') * 100);
            }
            if (!empty($priceRange)) {
                $attributes['Price'] = $priceRange;
            }
        }
        
        if (!empty($attributes)) {
            $params['attributeFilters'] = json_encode($attributes);
        }
        
        // Bouw de URL met parameters
        $url .= '?' . http_build_query($params);
        
        // Debug logging
        error_log("Marktplaats API URL: " . $url);
        
        // Haal data op
        $jsonString = getSimpleHTMLDOM($url);
        
        if (!$jsonString) {
            error_log("Marktplaats: Geen response van API");
            return;
        }
        
        // Debug: toon eerste deel van response
        error_log("Marktplaats Response: " . substr($jsonString, 0, 500));
        
        $jsonObj = json_decode($jsonString);
        
        // Controleer of we listings hebben
        if (!isset($jsonObj->listings) || empty($jsonObj->listings)) {
            error_log("Marktplaats: Geen listings gevonden");
            return;
        }
        
        // Bepaal of we globale advertenties moeten uitsluiten
        $excludeGlobal = !is_null($this->getInput('s')) && !$this->getInput('s');
        
        // Prijs filters voor client-side filtering
        $minPrice = !is_null($this->getInput('f')) ? (float)$this->getInput('f') : null;
        $maxPrice = !is_null($this->getInput('t')) ? (float)$this->getInput('t') : null;
        
        // Afstand filter
        $maxDistance = !is_null($this->getInput('d')) ? (float)$this->getInput('d') : null;
        
        foreach ($jsonObj->listings as $listing) {
            // Skip als het een globale advertentie is en we die niet willen
            if ($excludeGlobal && isset($listing->location->distanceMeters) && $listing->location->distanceMeters < 0) {
                continue;
            }
            
            // Filter op afstand
            if ($maxDistance !== null && isset($listing->location->distanceMeters)) {
                $distance = $listing->location->distanceMeters;
                if ($distance > 0 && $distance > $maxDistance) {
                    continue;
                }
            }
            
            // Haal prijs op
            $priceData = $this->getPriceFromListing($listing);
            $effectivePrice = $priceData['price'];
            
            // Filter op prijs
            if ($maxPrice !== null && $effectivePrice !== null && $effectivePrice > $maxPrice) {
                continue;
            }
            if ($minPrice !== null && $effectivePrice !== null && $effectivePrice < $minPrice) {
                continue;
            }
            
            // Bouw item
            $item = [];
            $item['uri'] = 'https://marktplaats.nl' . $listing->vipUrl;
            $item['title'] = $listing->title;
            $item['timestamp'] = $listing->date;
            $item['author'] = $listing->sellerInformation->sellerName ?? 'Onbekend';
            $item['uid'] = $listing->itemId;
            
            // Content opbouwen
            $content = '';
            
            if (!empty($listing->description)) {
                $content .= nl2br(htmlspecialchars($listing->description));
            }
            
            // Prijs
            $content .= "<br><br>\n<strong>" . $priceData['display'] . "</strong>";
            
            // Locatie met afstand
            $locationParts = [];
            if (!empty($listing->location->cityName)) {
                $locationParts[] = $listing->location->cityName;
            }
            if (isset($listing->location->distanceMeters)) {
                $distance = $listing->location->distanceMeters;
                if ($distance >= 0) {
                    if ($distance < 1000) {
                        $locationParts[] = $distance . 'm';
                    } else {
                        $locationParts[] = round($distance/1000, 1) . 'km';
                    }
                }
            }
            if (!empty($locationParts)) {
                $content .= "<br>\nLocatie: " . implode(' - ', $locationParts);
            }
            
            // Afbeeldingen
            if (!is_null($this->getInput('i')) && $this->getInput('i') && !empty($listing->imageUrls)) {
                $item['enclosures'] = $listing->imageUrls;
                foreach ($listing->imageUrls as $imgurl) {
                    $fullImageUrl = 'https://' . ltrim($imgurl, ':/');
                    $content .= "<br>\n<img src='" . $fullImageUrl . "' style='max-width:100%'>";
                }
            }
            
            // Raw data
            if (!is_null($this->getInput('r')) && $this->getInput('r')) {
                $content .= "<br><br>\n<pre>" . htmlspecialchars(json_encode($listing, JSON_PRETTY_PRINT)) . "</pre>";
                $content .= "<br>\n<small>URL: " . htmlspecialchars($url) . "</small>";
            }
            
            $item['content'] = $content;
            $this->items[] = $item;
        }
        
        error_log("Marktplaats: " . count($this->items) . " items gevonden");
    }
    
    private function getPriceFromListing($listing)
    {
        $result = [
            'price' => null,
            'type' => 'unknown',
            'display' => 'Prijs: Onbekend'
        ];
        
        if (!isset($listing->priceInfo)) {
            return $result;
        }
        
        $priceInfo = $listing->priceInfo;
        $modelType = $priceInfo->priceType ?? 'unknown';
        $result['type'] = $modelType;
        
        // Probeer verschillende prijsvelden
        $priceInCents = null;
        
        if (isset($priceInfo->priceCents) && $priceInfo->priceCents > 0) {
            $priceInCents = $priceInfo->priceCents;
        } elseif (isset($priceInfo->askingPrice) && $priceInfo->askingPrice > 0) {
            $priceInCents = $priceInfo->askingPrice;
        } elseif (isset($priceInfo->minimalBid) && $priceInfo->minimalBid > 0) {
            $priceInCents = $priceInfo->minimalBid;
        } elseif (isset($priceInfo->startingPrice) && $priceInfo->startingPrice > 0) {
            $priceInCents = $priceInfo->startingPrice;
        }
        
        if ($priceInCents !== null) {
            $result['price'] = $priceInCents / 100;
            $result['display'] = 'Prijs: €' . number_format($result['price'], 2, ',', '.');
            
            if ($modelType === 'fixed') {
                $result['display'] = 'Vaste prijs: €' . number_format($result['price'], 2, ',', '.');
            } elseif ($modelType === 'bidding') {
                $result['display'] = 'Bieden vanaf €' . number_format($result['price'], 2, ',', '.');
            }
        } elseif ($modelType === 'see description') {
            $result['display'] = 'Prijs: Zie beschrijving';
        } elseif ($modelType === 'bidding') {
            $result['display'] = 'Prijs: Bieden';
        }
        
        return $result;
    }

    public function getName()
    {
        if (!is_null($this->getInput('q'))) {
            $name = $this->getInput('q') . ' - Marktplaats';
            
            if (!is_null($this->getInput('z')) && !is_null($this->getInput('d'))) {
                $distanceKm = $this->getInput('d') / 1000;
                $name .= ' binnen ' . $distanceKm . 'km van ' . $this->getInput('z');
            }
            
            return $name;
        }
        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://www.marktplaats.nl/ico/favicon.ico';
    }

    // De scrape functies blijven hetzelfde
    private static function scrapeSubCategories()
    {
        // ... ongewijzigd
    }

    private static function printArrayAsCode($array, $indent = 0)
    {
        // ... ongewijzigd
    }

    public static function printScrapeArray()
    {
        // ... ongewijzigd
    }
}
