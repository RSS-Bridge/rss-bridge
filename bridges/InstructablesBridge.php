<?php

/**
* This class implements a bridge for http://www.instructables.com, supporting
* general feeds and feeds by category.
*
* Remarks:
* - For some reason it is very important to have the category URI end with a
*   slash, otherwise the site defaults to the main category (i.e. Technology)!
*   If you need to update the categories list, enable the 'listCategories'
*   function (see comments below) and run the bridge with format=Html (see page
*   source)
*/
class InstructablesBridge extends BridgeAbstract
{
    const NAME = 'Instructables Bridge';
    const URI = 'https://www.instructables.com';
    const DESCRIPTION = 'Returns general feeds and feeds by category';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        'Category' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Circuits' => [
                        'All' => '/circuits/',
                        'Apple' => '/circuits/apple/projects/',
                        'Arduino' => '/circuits/arduino/projects/',
                        'Art' => '/circuits/art/projects/',
                        'Assistive Tech' => '/circuits/assistive-tech/projects/',
                        'Audio' => '/circuits/audio/projects/',
                        'Cameras' => '/circuits/cameras/projects/',
                        'Clocks' => '/circuits/clocks/projects/',
                        'Computers' => '/circuits/computers/projects/',
                        'Electronics' => '/circuits/electronics/projects/',
                        'Gadgets' => '/circuits/gadgets/projects/',
                        'Lasers' => '/circuits/lasers/projects/',
                        'LEDs' => '/circuits/leds/projects/',
                        'Linux' => '/circuits/linux/projects/',
                        'Microcontrollers' => '/circuits/microcontrollers/projects/',
                        'Microsoft' => '/circuits/microsoft/projects/',
                        'Mobile' => '/circuits/mobile/projects/',
                        'Raspberry Pi' => '/circuits/raspberry-pi/projects/',
                        'Remote Control' => '/circuits/remote-control/projects/',
                        'Reuse' => '/circuits/reuse/projects/',
                        'Robots' => '/circuits/robots/projects/',
                        'Sensors' => '/circuits/sensors/projects/',
                        'Software' => '/circuits/software/projects/',
                        'Soldering' => '/circuits/soldering/projects/',
                        'Speakers' => '/circuits/speakers/projects/',
                        'Tools' => '/circuits/tools/projects/',
                        'USB' => '/circuits/usb/projects/',
                        'Wearables' => '/circuits/wearables/projects/',
                        'Websites' => '/circuits/websites/projects/',
                        'Wireless' => '/circuits/wireless/projects/',
                    ],
                    'Workshop' => [
                        'All' => '/workshop/',
                        '3D Printing' => '/workshop/3d-printing/projects/',
                        'Cars' => '/workshop/cars/projects/',
                        'CNC' => '/workshop/cnc/projects/',
                        'Electric Vehicles' => '/workshop/electric-vehicles/projects/',
                        'Energy' => '/workshop/energy/projects/',
                        'Furniture' => '/workshop/furniture/projects/',
                        'Home Improvement' => '/workshop/home-improvement/projects/',
                        'Home Theater' => '/workshop/home-theater/projects/',
                        'Hydroponics' => '/workshop/hydroponics/projects/',
                        'Knives' => '/workshop/knives/projects/',
                        'Laser Cutting' => '/workshop/laser-cutting/projects/',
                        'Lighting' => '/workshop/lighting/projects/',
                        'Metalworking' => '/workshop/metalworking/projects/',
                        'Molds & Casting' => '/workshop/molds-and-casting/projects/',
                        'Motorcycles' => '/workshop/motorcycles/projects/',
                        'Organizing' => '/workshop/organizing/projects/',
                        'Pallets' => '/workshop/pallets/projects/',
                        'Repair' => '/workshop/repair/projects/',
                        'Science' => '/workshop/science/projects/',
                        'Shelves' => '/workshop/shelves/projects/',
                        'Solar' => '/workshop/solar/projects/',
                        'Tools' => '/workshop/tools/projects/',
                        'Woodworking' => '/workshop/woodworking/projects/',
                        'Workbenches' => '/workshop/workbenches/projects/',
                    ],
                    'Craft' => [
                        'All' => '/craft/',
                        'Art' => '/craft/art/projects/',
                        'Books & Journals' => '/craft/books-and-journals/projects/',
                        'Cardboard' => '/craft/cardboard/projects/',
                        'Cards' => '/craft/cards/projects/',
                        'Clay' => '/craft/clay/projects/',
                        'Costumes & Cosplay' => '/craft/costumes-and-cosplay/projects/',
                        'Digital Graphics' => '/craft/digital-graphics/projects/',
                        'Duct Tape' => '/craft/duct-tape/projects/',
                        'Embroidery' => '/craft/embroidery/projects/',
                        'Fashion' => '/craft/fashion/projects/',
                        'Felt' => '/craft/felt/projects/',
                        'Fiber Arts' => '/craft/fiber-arts/projects/',
                        'Gift Wrapping' => '/craft/gift-wrapping/projects/',
                        'Jewelry' => '/craft/jewelry/projects/',
                        'Knitting & Crochet' => '/craft/knitting-and-crochet/projects/',
                        'Leather' => '/craft/leather/projects/',
                        'Mason Jars' => '/craft/mason-jars/projects/',
                        'No-Sew' => '/craft/no-sew/projects/',
                        'Paper' => '/craft/paper/projects/',
                        'Parties & Weddings' => '/craft/parties-and-weddings/projects/',
                        'Photography' => '/craft/photography/projects/',
                        'Printmaking' => '/craft/printmaking/projects/',
                        'Reuse' => '/craft/reuse/projects/',
                        'Sewing' => '/craft/sewing/projects/',
                        'Soapmaking' => '/craft/soapmaking/projects/',
                        'Wallets' => '/craft/wallets/projects/',
                    ],
                    'Cooking' => [
                        'All' => '/cooking/',
                        'Bacon' => '/cooking/bacon/projects/',
                        'BBQ & Grilling' => '/cooking/bbq-and-grilling/projects/',
                        'Beverages' => '/cooking/beverages/projects/',
                        'Bread' => '/cooking/bread/projects/',
                        'Breakfast' => '/cooking/breakfast/projects/',
                        'Cake' => '/cooking/cake/projects/',
                        'Candy' => '/cooking/candy/projects/',
                        'Canning & Preserving' => '/cooking/canning-and-preserving/projects/',
                        'Cocktails & Mocktails' => '/cooking/cocktails-and-mocktails/projects/',
                        'Coffee' => '/cooking/coffee/projects/',
                        'Cookies' => '/cooking/cookies/projects/',
                        'Cupcakes' => '/cooking/cupcakes/projects/',
                        'Dessert' => '/cooking/dessert/projects/',
                        'Homebrew' => '/cooking/homebrew/projects/',
                        'Main Course' => '/cooking/main-course/projects/',
                        'Pasta' => '/cooking/pasta/projects/',
                        'Pie' => '/cooking/pie/projects/',
                        'Pizza' => '/cooking/pizza/projects/',
                        'Salad' => '/cooking/salad/projects/',
                        'Sandwiches' => '/cooking/sandwiches/projects/',
                        'Snacks & Appetizers' => '/cooking/snacks-and-appetizers/projects/',
                        'Soups & Stews' => '/cooking/soups-and-stews/projects/',
                        'Vegetarian & Vegan' => '/cooking/vegetarian-and-vegan/projects/',
                    ],
                    'Living' => [
                        'All' => '/living/',
                        'Beauty' => '/living/beauty/projects/',
                        'Christmas' => '/living/christmas/projects/',
                        'Cleaning' => '/living/cleaning/projects/',
                        'Decorating' => '/living/decorating/projects/',
                        'Education' => '/living/education/projects/',
                        'Gardening' => '/living/gardening/projects/',
                        'Halloween' => '/living/halloween/projects/',
                        'Health' => '/living/health/projects/',
                        'Hiding Places' => '/living/hiding-places/projects/',
                        'Holidays' => '/living/holidays/projects/',
                        'Homesteading' => '/living/homesteading/projects/',
                        'Kids' => '/living/kids/projects/',
                        'Kitchen' => '/living/kitchen/projects/',
                        'LEGO & KNEX' => '/living/lego-and-knex/projects/',
                        'Life Hacks' => '/living/life-hacks/projects/',
                        'Music' => '/living/music/projects/',
                        'Office Supply Hacks' => '/living/office-supply-hacks/projects/',
                        'Organizing' => '/living/organizing/projects/',
                        'Pest Control' => '/living/pest-control/projects/',
                        'Pets' => '/living/pets/projects/',
                        'Pranks, Tricks, & Humor' => '/living/pranks-tricks-and-humor/projects/',
                        'Relationships' => '/living/relationships/projects/',
                        'Toys & Games' => '/living/toys-and-games/projects/',
                        'Travel' => '/living/travel/projects/',
                        'Video Games' => '/living/video-games/projects/',
                    ],
                    'Outside' => [
                        'All' => '/outside/',
                        'Backyard' => '/outside/backyard/projects/',
                        'Beach' => '/outside/beach/projects/',
                        'Bikes' => '/outside/bikes/projects/',
                        'Birding' => '/outside/birding/projects/',
                        'Boats' => '/outside/boats/projects/',
                        'Camping' => '/outside/camping/projects/',
                        'Climbing' => '/outside/climbing/projects/',
                        'Fire' => '/outside/fire/projects/',
                        'Fishing' => '/outside/fishing/projects/',
                        'Hunting' => '/outside/hunting/projects/',
                        'Kites' => '/outside/kites/projects/',
                        'Knots' => '/outside/knots/projects/',
                        'Launchers' => '/outside/launchers/projects/',
                        'Paracord' => '/outside/paracord/projects/',
                        'Rockets' => '/outside/rockets/projects/',
                        'Siege Engines' => '/outside/siege-engines/projects/',
                        'Skateboarding' => '/outside/skateboarding/projects/',
                        'Snow' => '/outside/snow/projects/',
                        'Sports' => '/outside/sports/projects/',
                        'Survival' => '/outside/survival/projects/',
                        'Water' => '/outside/water/projects/',
                    ],
                    'Makeymakey' => [
                        'All' => '/makeymakey/',
                        'Makey Makey on Instructables' => '/makeymakey/',
                    ],
                    'Teachers' => [
                        'All' => '/teachers/',
                        'ELA' => '/teachers/ela/projects/',
                        'Math' => '/teachers/math/projects/',
                        'Science' => '/teachers/science/projects/',
                        'Social Studies' => '/teachers/social-studies/projects/',
                        'Engineering' => '/teachers/engineering/projects/',
                        'Coding' => '/teachers/coding/projects/',
                        'Electronics' => '/teachers/electronics/projects/',
                        'Robotics' => '/teachers/robotics/projects/',
                        'Arduino' => '/teachers/arduino/projects/',
                        'CNC' => '/teachers/cnc/projects/',
                        'Laser Cutting' => '/teachers/laser-cutting/projects/',
                        '3D Printing' => '/teachers/3d-printing/projects/',
                        '3D Design' => '/teachers/3d-design/projects/',
                        'Art' => '/teachers/art/projects/',
                        'Music' => '/teachers/music/projects/',
                        'Theatre' => '/teachers/theatre/projects/',
                        'Wood Shop' => '/teachers/wood-shop/projects/',
                        'Metal Shop' => '/teachers/metal-shop/projects/',
                        'Resources' => '/teachers/resources/projects/',
                    ],
                ],
                'title' => 'Select your category (required)',
                'defaultValue' => 'Circuits'
            ],
            'filter' => [
                'name' => 'Filter',
                'type' => 'list',
                'values' => [
                    'Featured' => ' ',
                    'Recent' => 'recent/',
                    'Popular' => 'popular/',
                    'Views' => 'views/',
                    'Contest Winners' => 'winners/'
                ],
                'title' => 'Select a filter',
                'defaultValue' => 'Featured'
            ]
        ]
    ];

    public function collectData()
    {
        $category = $this->getInput('category');
        $filter = $this->getInput('filter');

        $api = 'https://www.instructables.com/api_proxy/search/collections/projects/documents/search';
        //$sortBy = 'views:desc';
        $sortBy = 'publishDate:desc';
        //$filterBy = 'featureFlag:=true && category:=Circuits && channel: [Apple,Linux]';
        $filterBy = 'featureFlag:=true && category:=Circuits';
        //$filterBy = 'featureFlag:=true && teachers:=Teachers';
        //$filterBy = 'featureFlag:=true && category:=Craft';
        $params = [
            'q'                 => '*',
            'query_by'          => 'title,stepBody,screenName',
            'page'              => '1',
            'sort_by'           => $sortBy,
            'include_fields'    => 'title,urlString,coverImageUrl,screenName,favorites,views,primaryClassification,featureFlag,prizeLevel,IMadeItCount',
            'filter_by'         => $filterBy,
            'per_page'          => '50',
        ];

        $url = $api . '?' . http_build_query($params);
        /* phpcs:ignore */
        $key = 'TUIxY0xkNjdHV09KaFV1dEVxYVRHNGs1QW1sbzlNVVZBaVZKV2VrODc0VT02ZWFYeyJleGNsdWRlX2ZpZWxkcyI6WyJvdXRfb2YiLCJzZWFyY2hfdGltZV9tcyIsInN0ZXBCb2R5Il0sInBlcl9wYWdlIjo2MH0=';
        $json = getContents($url, ["x-typesense-api-key: $key"]);
        $data = Json::decode($json, false);

        foreach ($data->hits as $hit) {
            $document = $hit->document;
            $item = [];
            $item['uri'] = 'https://www.instructables.com/' . $document->urlString;
            $item['author'] = $document->screenName;
            $item['title'] = $document->title;
            $item['content'] = '<pre>' . Json::encode($document) . '</pre>';
            $item['enclosures'] = [$document->coverImageUrl];
            $this->items[] = $item;
        }
    }
}
