<?php

class BukowskisBridge extends BridgeAbstract
{
    const NAME = 'Bukowskis';
    const URI = 'https://www.bukowskis.com';
    const DESCRIPTION = 'Fetches info about auction objects from Bukowskis auction house';
    const MAINTAINER = 'Qluxzz';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'All categories' => '',
                'Art' => [
                    'All' => 'art',
                    'Classic Art' => 'art.classic-art',
                    'Classic Finnish Art' => 'art.classic-finnish-art',
                    'Classic Swedish Art' => 'art.classic-swedish-art',
                    'Contemporary' => 'art.contemporary',
                    'Modern Finnish Art' => 'art.modern-finnish-art',
                    'Modern International Art' => 'art.modern-international-art',
                    'Modern Swedish Art' => 'art.modern-swedish-art',
                    'Old Masters' => 'art.old-masters',
                    'Other' => 'art.other',
                    'Photographs' => 'art.photographs',
                    'Prints' => 'art.prints',
                    'Sculpture' => 'art.sculpture',
                    'Swedish Old Masters' => 'art.swedish-old-masters',
                ],
                'Asian Ceramics & Works of Art' => [
                    'All' => 'asian-ceramics-works-of-art',
                    'Other' => 'asian-ceramics-works-of-art.other',
                    'Porcelain' => 'asian-ceramics-works-of-art.porcelain',
                ],
                'Books & Manuscripts' => [
                    'All' => 'books-manuscripts',
                    'Books' => 'books-manuscripts.books',
                ],
                'Carpets, rugs & textiles' => [
                    'All' => 'carpets-rugs-textiles',
                    'European' => 'carpets-rugs-textiles.european',
                    'Oriental' => 'carpets-rugs-textiles.oriental',
                    'Rest of the world' => 'carpets-rugs-textiles.rest-of-the-world',
                    'Scandinavian' => 'carpets-rugs-textiles.scandinavian',
                ],
                'Ceramics & porcelain' => [
                    'All' => 'ceramics-porcelain',
                    'Ceramic ware' => 'ceramics-porcelain.ceramic-ware',
                    'European' => 'ceramics-porcelain.european',
                    'Rest of the world' => 'ceramics-porcelain.rest-of-the-world',
                    'Scandinavian' => 'ceramics-porcelain.scandinavian',
                ],
                'Collectibles' => [
                    'All' => 'collectibles',
                    'Advertising & Retail' => 'collectibles.advertising-retail',
                    'Memorabilia' => 'collectibles.memorabilia',
                    'Movies & music' => 'collectibles.movies-music',
                    'Other' => 'collectibles.other',
                    'Retro & Popular Culture' => 'collectibles.retro-popular-culture',
                    'Technica & Nautica' => 'collectibles.technica-nautica',
                    'Toys' => 'collectibles.toys',
                ],
                'Design' => [
                    'All' => 'design',
                    'Art glass' => 'design.art-glass',
                    'Furniture' => 'design.furniture',
                    'Other' => 'design.other',
                ],
                'Folk art' => [
                    'All' => 'folk-art',
                    'All categories' => 'lots',
                ],
                'Furniture' => [
                    'All' => 'furniture',
                    'Armchairs & Sofas' => 'furniture.armchairs-sofas',
                    'Cabinets & Bureaus' => 'furniture.cabinets-bureaus',
                    'Chairs' => 'furniture.chairs',
                    'Garden furniture' => 'furniture.garden-furniture',
                    'Mirrors' => 'furniture.mirrors',
                    'Other' => 'furniture.other',
                    'Shelves & Book cases' => 'furniture.shelves-book-cases',
                    'Tables' => 'furniture.tables',
                ],
                'Glassware' => [
                    'All' => 'glassware',
                    'Glassware' => 'glassware.glassware',
                    'Other' => 'glassware.other',
                ],
                'Jewellery' => [
                    'All' => 'jewellery',
                    'Bracelets' => 'jewellery.bracelets',
                    'Brooches' => 'jewellery.brooches',
                    'Earrings' => 'jewellery.earrings',
                    'Necklaces & Pendants' => 'jewellery.necklaces-pendants',
                    'Other' => 'jewellery.other',
                    'Rings' => 'jewellery.rings',
                ],
                'Lighting' => [
                    'All' => 'lighting',
                    'Candle sticks & Candelabras' => 'lighting.candle-sticks-candelabras',
                    'Ceiling lights' => 'lighting.ceiling-lights',
                    'Chandeliers' => 'lighting.chandeliers',
                    'Floor lights' => 'lighting.floor-lights',
                    'Other' => 'lighting.other',
                    'Table lights' => 'lighting.table-lights',
                    'Wall lights' => 'lighting.wall-lights',
                ],
                'Militaria' => [
                    'All' => 'militaria',
                    'Honors & Medals' => 'militaria.honors-medals',
                    'Other militaria' => 'militaria.other-militaria',
                    'Weaponry' => 'militaria.weaponry',
                ],
                'Miscellaneous' => [
                    'All' => 'miscellaneous',
                    'Brass, Copper & Pewter' => 'miscellaneous.brass-copper-pewter',
                    'Nickel silver' => 'miscellaneous.nickel-silver',
                    'Oriental' => 'miscellaneous.oriental',
                    'Other' => 'miscellaneous.other',
                ],
                'Silver' => [
                    'All' => 'silver',
                    'Candle sticks' => 'silver.candle-sticks',
                    'Cups & Bowls' => 'silver.cups-bowls',
                    'Cutlery' => 'silver.cutlery',
                    'Other' => 'silver.other',
                ],
                'Timepieces' => [
                    'All' => 'timepieces',
                    'Other' => 'timepieces.other',
                    'Pocket watches' => 'timepieces.pocket-watches',
                    'Table clocks' => 'timepieces.table-clocks',
                    'Wrist watches' => 'timepieces.wrist-watches',
                ],
                'Vintage & Fashion' => [
                    'All' => 'vintage-fashion',
                    'Accessories' => 'vintage-fashion.accessories',
                    'Bags & Trunks' => 'vintage-fashion.bags-trunks',
                    'Clothes' => 'vintage-fashion.clothes',
                ],
            ]
        ],
        'sort_order' => [
            'name' => 'Sort order',
            'type' => 'list',
            'values' => [
                'Ending soon' => 'ending',
                'Most recent' => 'recent',
                'Most bids' => 'most',
                'Fewest bids' => 'fewest',
                'Lowest price' => 'lowest',
                'Highest price' => 'highest',
                'Lowest estimate' => 'low',
                'Highest estimate' => 'high',
                'Alphabetical' => 'alphabetical',
            ],
        ],
        'language' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'English' => 'en',
                'Swedish' => 'sv',
                'Finnish' => 'fi'
            ],
        ],
    ]];

    const CACHE_TIMEOUT = 3600; // 1 hour

    private $title;

    public function collectData()
    {
        $baseUrl = 'https://www.bukowskis.com';
        $category = $this->getInput('category');
        $language = $this->getInput('language');
        $sort_order = $this->getInput('sort_order');

        $url = $baseUrl . '/' . $language . '/lots';

        if ($category) {
            $url = $url . '/category/' . $category;
        }

        if ($sort_order) {
            $url = $url . '/sort/' . $sort_order;
        }

        $html = getSimpleHTMLDOM($url);

        $this->title = htmlspecialchars_decode($html->find('title', 0)->innertext);

        foreach ($html->find('div.c-lot-index-lot') as $lot) {
            $title = $lot->find('a.c-lot-index-lot__title', 0)->plaintext;
            $relative_url = $lot->find('a.c-lot-index-lot__link', 0)->href;
            $images = json_decode(
                htmlspecialchars_decode(
                    $lot
                        ->find('img.o-aspect-ratio__image', 0)
                        ->getAttribute('data-thumbnails')
                )
            );

            $this->items[] = [
                'title' => $title,
                'uri' => $baseUrl . $relative_url,
                'uid' => $lot->getAttribute('data-lot-id'),
                'content' => count($images) > 0 ? "<img src='$images[0]'/><br/>$title" : $title,
                'enclosures' => array_slice($images, 1),
            ];
        }
    }

    public function getName()
    {
        return $this->title ?: parent::getName();
    }
}
