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
        if (!empty($this->getInput('c'))) {
            $query .= '&l1CategoryId=' . $this->getInput('c');
        }
        if (!is_null($this->getInput('sc'))) {
            $query .= '&l2CategoryId=' . $this->getInput('sc');
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
                            $item['content'] .= "<br />\n<img alt='' src='https:" . $imgurl . "' />";
                        }
                    } else {
                        $item['content'] .= "<br>\n<img alt='' src='https:" . $listing->imageUrls . "' />";
                    }
                }
                if (!is_null($this->getInput('r'))) {
                    if ($this->getInput('r')) {
                        $item['content'] .= "<br />\n<br />\n<br />\n" . json_encode($listing) . "<br />$url";
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

    /**
     * Method can be used to scrape the subcategories from marktplaats
     */
    private static function scrapeSubCategories()
    {
        $main = [];
        $main['Select a category'] = '';
        $marktplaatsHTML = file_get_html('https://www.marktplaats.nl');
        foreach ($marktplaatsHTML->find('select[id=categoryId] option') as $opt) {
            if (!str_contains($opt->innertext, 'categorie')) {
                $main[$opt->innertext] = $opt->value;
                $ids[] = $opt->value;
            }
        }

        $result = [];
        foreach ($ids as $id) {
            $url = 'https://www.marktplaats.nl/lrp/api/search?l1CategoryId=' . $id;
            $jsonstring = getContents($url);
            $jsondata = json_decode((string)$jsonstring);
            if (isset($jsondata->searchCategoryOptions)) {
                $categories = $jsondata->searchCategoryOptions;
                if (isset($jsondata->categoriesById->$id)) {
                    $maincategory = $jsondata->categoriesById->$id;
                    $array = [];
                    foreach ($categories as $categorie) {
                        $array[$categorie->fullName] = $categorie->id;
                    }
                    $result[$maincategory->fullName] = $array;
                }
            } else {
                print($jsonstring);
            }
        }
        $combinedResult = [
            'main' => $main,
            'sub' => $result
        ];
        return $combinedResult;
    }

    /**
     * Helper method to construct the array that could be used for categories
     *
     * @param $array
     * @param $indent
     * @return void
     */
    private static function printArrayAsCode($array, $indent = 0)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                echo str_repeat('    ', $indent) . "'$key' => [" . PHP_EOL;
                self::printArrayAsCode($value, $indent + 1);
                echo str_repeat('    ', $indent) . '],' . PHP_EOL;
            } else {
                $value = str_replace('\'', '\\\'', $value);
                $key = str_replace('\'', '\\\'', $key);
                echo str_repeat('    ', $indent) . "'$key' => '$value'," . PHP_EOL;
            }
        }
    }

    private static function printScrapeArray()
    {
        $array = (MarktplaatsBridge::scrapeSubCategories());

        echo '$myArray = [' . PHP_EOL;
        self::printArrayAsCode($array['main'], 1);
        echo '];' . PHP_EOL;

        echo '$myArray = [' . PHP_EOL;
        self::printArrayAsCode($array['sub'], 1);
        echo '];' . PHP_EOL;
    }
}
