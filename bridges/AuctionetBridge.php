<?php

class AuctionetBridge extends BridgeAbstract
{
    const NAME = 'Auctionet';
    const URI = 'https://www.auctionet.com';
    const DESCRIPTION = 'Fetches info about auction objects from Auctionet (an auction platform for many European auction houses)';
    const MAINTAINER = 'Qluxzz';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'All categories' => '',
                'Art' => [
                    'All' => '25-art',
                    'Drawings' => '119-drawings',
                    'Engravings & Prints' => '27-engravings-prints',
                    'Other' => '30-other',
                    'Paintings' => '28-paintings',
                    'Photography' => '26-photography',
                    'Sculptures & Bronzes' => '29-sculptures-bronzes',
                ],
                'Asiatica' => [
                    'All' => '117-asiatica',
                ],
                'Books, Maps & Manuscripts' => [
                    'All' => '50-books-maps-manuscripts',
                    'Autographs & Manuscripts' => '206-autographs-manuscripts',
                    'Books' => '204-books',
                    'Maps' => '205-maps',
                    'Other' => '207-other',
                ],
                'Carpets & Textiles' => [
                    'All' => '35-carpets-textiles',
                    'Carpets' => '36-carpets',
                    'Textiles' => '37-textiles',
                ],
                'Ceramics & Porcelain' => [
                    'All' => '9-ceramics-porcelain',
                    'European' => '10-european',
                    'Oriental' => '11-oriental',
                    'Rest of the world' => '12-rest-of-the-world',
                    'Tableware' => '210-tableware',
                ],
                'Clocks & Watches' => [
                    'All' => '31-clocks-watches',
                    'Carriage & Miniature Clocks' => '258-carriage-miniature-clocks',
                    'Longcase clocks' => '32-longcase-clocks',
                    'Mantel clocks' => '33-mantel-clocks',
                    'Other clocks' => '34-other-clocks',
                    'Pocket & Stop Watches' => '110-pocket-stop-watches',
                    'Wall Clocks' => '127-wall-clocks',
                    'Wristwatches' => '15-wristwatches',
                ],
                'Coins, Medals & Stamps' => [
                    'All' => '46-coins-medals-stamps',
                    'Coins' => '128-coins',
                    'Orders & Medals' => '135-orders-medals',
                    'Other' => '131-other',
                    'Stamps' => '136-stamps',
                ],
                'Folk art' => [
                    'All' => '58-folk-art',
                    'Bowls & Boxes' => '121-bowls-boxes',
                    'Furniture' => '122-furniture',
                    'Other' => '123-other',
                    'Tools & Gears' => '120-tools-gears',
                ],
                'Furniture' => [
                    'All' => '16-furniture',
                    'Armchairs & Chairs' => '18-armchairs-chairs',
                    'Chests of drawers' => '24-chests-of-drawers',
                    'Cupboards, Cabinets & Shelves' => '23-cupboards-cabinets-shelves',
                    'Dining room furniture' => '22-dining-room-furniture',
                    'Garden' => '21-garden',
                    'Other' => '17-other',
                    'Sofas & seatings' => '20-sofas-seatings',
                    'Tables' => '19-tables',
                ],
                'Glass' => [
                    'All' => '6-glass',
                    'Art glass' => '208-art-glass',
                    'Other' => '8-other',
                    'Tableware' => '7-tableware',
                    'Utility glass' => '209-utility-glass',
                ],
                'Jewellery & Gemstones' => [
                    'All' => '13-jewellery-gemstones',
                    'Alliance rings' => '113-alliance-rings',
                    'Bracelets' => '106-bracelets',
                    'Brooches & Pendants' => '107-brooches-pendants',
                    'Costume Jewellery' => '259-costume-jewellery',
                    'Cufflinks & Tie Pins' => '111-cufflinks-tie-pins',
                    'Ear studs' => '116-ear-studs',
                    'Earrings' => '115-earrings',
                    'Gemstones' => '48-gemstones',
                    'Jewellery' => '14-jewellery',
                    'Jewellery Suites' => '109-jewellery-suites',
                    'Necklace' => '104-necklace',
                    'Other' => '118-other',
                    'Rings' => '112-rings',
                    'Signet rings' => '105-signet-rings',
                    'Solitaire rings' => '114-solitaire-rings',
                ],
                'Licence weapons' => [
                    'All' => '59-licence-weapons',
                    'Combi/Combo' => '63-combi-combo',
                    'Double express rifles' => '60-double-express-rifles',
                    'Rifles' => '61-rifles',
                    'Shotguns' => '62-shotguns',
                ],
                'Lighting & Lamps' => [
                    'All' => '1-lighting-lamps',
                    'Candlesticks' => '4-candlesticks',
                    'Ceiling lights' => '3-ceiling-lights',
                    'Chandeliers' => '203-chandeliers',
                    'Floor lights' => '2-floor-lights',
                    'Other lighting' => '5-other-lighting',
                    'Table Lamps' => '125-table-lamps',
                    'Wall Lights' => '124-wall-lights',
                ],
                'Mirrors' => [
                    'All' => '42-mirrors',
                ],
                'Miscellaneous' => [
                    'All' => '43-miscellaneous',
                    'Fishing equipment' => '54-fishing-equipment',
                    'Miscellaneous' => '47-miscellaneous',
                    'Modern Tools' => '133-modern-tools',
                    'Modern consumer electronics' => '52-modern-consumer-electronics',
                    'Musical instruments' => '51-musical-instruments',
                    'Technica & Nautica' => '45-technica-nautica',
                ],
                'Photo, Cameras & Lenses' => [
                    'All' => '57-photo-cameras-lenses',
                    'Cameras & accessories' => '71-cameras-accessories',
                    'Optics' => '66-optics',
                    'Other' => '72-other',
                ],
                'Silver & Metals' => [
                    'All' => '38-silver-metals',
                    'Other metals' => '40-other-metals',
                    'Pewter, Brass & Copper' => '41-pewter-brass-copper',
                    'Silver' => '39-silver',
                    'Silver plated' => '213-silver-plated',
                ],
                'Toys' => [
                    'All' => '44-toys',
                    'Comics' => '211-comics',
                    'Toys' => '212-toys',
                ],
                'Tribal art' => [
                    'All' => '134-tribal-art',
                ],
                'Vehicles, Boats & Parts' => [
                    'All' => '249-vehicles-boats-parts',
                    'Automobilia & Transport' => '255-automobilia-transport',
                    'Bicycles' => '132-bicycles',
                    'Boats & Accessories' => '250-boats-accessories',
                    'Car parts' => '253-car-parts',
                    'Cars' => '215-cars',
                    'Moped parts' => '254-moped-parts',
                    'Mopeds' => '216-mopeds',
                    'Motorcycle parts' => '252-motorcycle-parts',
                    'Motorcycles' => '251-motorcycles',
                    'Other' => '256-other',
                ],
                'Vintage & Designer Fashion' => [
                    'All' => '49-vintage-designer-fashion',
                ],
                'Weapons & Militaria' => [
                    'All' => '137-weapons-militaria',
                    'Airguns' => '257-airguns',
                    'Armour & Uniform' => '138-armour-uniform',
                    'Edged weapons' => '130-edged-weapons',
                    'Guns & Rifles' => '129-guns-rifles',
                    'Other' => '214-other',
                ],
                'Wine, Port & Spirits' => [
                    'All' => '170-wine-port-spirits',
                ],
            ]
        ],
        'sort_order' => [
            'name' => 'Sort order',
            'type' => 'list',
            'values' => [
                'Most bids' => 'bids_count_desc',
                'Lowest bid' => 'bid_asc',
                'Highest bid' => 'bid_desc',
                'Last bid on' => 'bid_on',
                'Ending soonest' => 'end_asc_active',
                'Lowest estimate' => 'estimate_asc',
                'Highest estimate' => 'estimate_desc',
                'Recently added' => 'recent'
            ],
        ],
        'country' => [
            'name' => 'Country',
            'type' => 'list',
            'values' => [
                'All' => '',
                'Denmark' => 'DK',
                'Finland' => 'FI',
                'Germany' => 'DE',
                'Spain' => 'ES',
                'Sweden' => 'SE',
                'United Kingdom' => 'GB'
            ]
        ],
        'language' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'English' => 'en',
                'Español' => 'es',
                'Deutsch' => 'de',
                'Svenska' => 'sv',
                'Dansk' => 'da',
                'Suomi' => 'fi',
            ],
        ],
    ]];

    const CACHE_TIMEOUT = 3600; // 1 hour

    private $title;

    public function collectData()
    {
        // Each page contains 48 auctions
        // So we fetch 10 pages so we decrease the likelihood
        // of missing auctions between feed refreshes

        // Fetch first page and use that to get title
        {
            $url = $this->getUrl(1);
            $data = getContents($url);

            $title = $this->getDocumentTitle($data);

            $this->items = array_merge($this->items, $this->parsePageData($data));
        }

        // Fetch remaining pages
        for ($page = 2; $page <= 10; $page++) {
            $url = $this->getUrl($page);

            $data = getContents($url);

            $this->items = array_merge($this->items, $this->parsePageData($data));
        }
    }

    public function getName()
    {
        return $this->title ?: parent::getName();
    }


    /* HELPERS */

    private function getUrl($page)
    {
        $category = $this->getInput('category');
        $language = $this->getInput('language');
        $sort_order = $this->getInput('sort_order');
        $country = $this->getInput('country');

        $url = self::URI . '/' . $language . '/search';

        if ($category) {
            $url = $url . '/' . $category;
        }

        $query = [];
        $query['page'] = $page;

        if ($sort_order) {
            $query['order'] = $sort_order;
        }

        if ($country) {
            $query['country_code'] = $country;
        }

        if (count($query) > 0) {
            $url = $url . '?' . http_build_query($query);
        }

        return $url;
    }

    private function getDocumentTitle($data)
    {
        $title_elem = '<title>';
        $title_elem_length = strlen($title_elem);
        $title_start = strpos($data, $title_elem);
        $title_end = strpos($data, '</title>', $title_start);
        $title_length = $title_end - $title_start + strlen($title_elem);
        $title = substr($data, $title_start + strlen($title_elem), $title_length);

        return $title;
    }

    /**
     * The auction items data is included in the HTML document
     * as a HTML entities encoded JSON structure
     * which is used to hydrate the React component for the list of auctions
     */
    private function parsePageData($data)
    {
        $key = 'data-react-props="';
        $keyLength = strlen($key);

        $start = strpos($data, $key);
        $end = strpos($data, '"', $start + strlen($key));
        $length = $end - ($start + $keyLength);

        $jsonString = substr($data, $start + $keyLength, $length);

        $jsonData = json_decode(htmlspecialchars_decode($jsonString), false);

        $items = [];

        foreach ($jsonData->{'items'} as $item) {
            $title = $item->{'longTitle'};
            $relative_url = $item->{'url'};
            $images = $item->{'imageUrls'};
            $id = $item->{'auctionId'};

            $items[] = [
                'title' => $title,
                'uri' => self::URI . $relative_url,
                'uid' => $id,
                'content' => count($images) > 0 ? "<img src='$images[0]'/><br/>$title" : $title,
                'enclosures' => array_slice($images, 1),
            ];
        }

        return $items;
    }
}
