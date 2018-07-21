<?php
/**
* This class implements a bridge for http://www.instructables.com, supporting
* general feeds and feeds by category. Instructables doesn't support HTTPS as
* of now (23.06.2018), so all connections are insecure!
*
* Remarks:
* - For some reason it is very important to have the category URI end with a
*	slash, otherwise the site defaults to the main category (i.e. Technology)!
*	If you need to update the categories list, enable the 'listCategories'
*	function (see comments below) and run the bridge with format=Html (see page
*	source)
*/
class InstructablesBridge extends BridgeAbstract {
	const NAME = 'Instructables Bridge';
	const URI = 'http://www.instructables.com';
	const DESCRIPTION = 'Returns general feeds and feeds by category';
	const MAINTAINER = 'logmanoriginal';
	const PARAMETERS = array(
		'Category' => array(
			'category' => array(
				'name' => 'Category',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Play' => array(
						'All' => '/play/',
						'KNEX' => '/play/knex/',
						'Offbeat' => '/play/offbeat/',
						'Lego' => '/play/lego/',
						'Airsoft' => '/play/airsoft/',
						'Card Games' => '/play/card-games/',
						'Guitars' => '/play/guitars/',
						'Instruments' => '/play/instruments/',
						'Magic Tricks' => '/play/magic-tricks/',
						'Minecraft' => '/play/minecraft/',
						'Music' => '/play/music/',
						'Nerf' => '/play/nerf/',
						'Nintendo' => '/play/nintendo/',
						'Office Supplies' => '/play/office-supplies/',
						'Paintball' => '/play/paintball/',
						'Paper Airplanes' => '/play/paper-airplanes/',
						'Party Tricks' => '/play/party-tricks/',
						'PlayStation' => '/play/playstation/',
						'Pranks and Humor' => '/play/pranks-and-humor/',
						'Puzzles' => '/play/puzzles/',
						'Siege Engines' => '/play/siege-engines/',
						'Sports' => '/play/sports/',
						'Table Top' => '/play/table-top/',
						'Toys' => '/play/toys/',
						'Video Games' => '/play/video-games/',
						'Wii' => '/play/wii/',
						'Xbox' => '/play/xbox/',
						'Yo-Yo' => '/play/yo-yo/',
					),
					'Craft' => array(
						'All' => '/craft/',
						'Art' => '/craft/art/',
						'Sewing' => '/craft/sewing/',
						'Paper' => '/craft/paper/',
						'Jewelry' => '/craft/jewelry/',
						'Fashion' => '/craft/fashion/',
						'Books & Journals' => '/craft/books-and-journals/',
						'Cards' => '/craft/cards/',
						'Clay' => '/craft/clay/',
						'Duct Tape' => '/craft/duct-tape/',
						'Embroidery' => '/craft/embroidery/',
						'Felt' => '/craft/felt/',
						'Fiber Arts' => '/craft/fiber-arts/',
						'Gifts & Wrapping' => '/craft/gifts-and-wrapping/',
						'Knitting & Crocheting' => '/craft/knitting-and-crocheting/',
						'Leather' => '/craft/leather/',
						'Mason Jars' => '/craft/mason-jars/',
						'No-Sew' => '/craft/no-sew/',
						'Parties & Weddings' => '/craft/parties-and-weddings/',
						'Print Making' => '/craft/print-making/',
						'Soap' => '/craft/soap/',
						'Wallets' => '/craft/wallets/',
					),
					'Technology' => array(
						'All' => '/technology/',
						'Electronics' => '/technology/electronics/',
						'Arduino' => '/technology/arduino/',
						'Photography' => '/technology/photography/',
						'Leds' => '/technology/leds/',
						'Science' => '/technology/science/',
						'Reuse' => '/technology/reuse/',
						'Apple' => '/technology/apple/',
						'Computers' => '/technology/computers/',
						'3D Printing' => '/technology/3D-Printing/',
						'Robots' => '/technology/robots/',
						'Art' => '/technology/art/',
						'Assistive Tech' => '/technology/assistive-technology/',
						'Audio' => '/technology/audio/',
						'Clocks' => '/technology/clocks/',
						'CNC' => '/technology/cnc/',
						'Digital Graphics' => '/technology/digital-graphics/',
						'Gadgets' => '/technology/gadgets/',
						'Kits' => '/technology/kits/',
						'Laptops' => '/technology/laptops/',
						'Lasers' => '/technology/lasers/',
						'Linux' => '/technology/linux/',
						'Microcontrollers' => '/technology/microcontrollers/',
						'Microsoft' => '/technology/microsoft/',
						'Mobile' => '/technology/mobile/',
						'Raspberry Pi' => '/technology/raspberry-pi/',
						'Remote Control' => '/technology/remote-control/',
						'Sensors' => '/technology/sensors/',
						'Software' => '/technology/software/',
						'Soldering' => '/technology/soldering/',
						'Speakers' => '/technology/speakers/',
						'Steampunk' => '/technology/steampunk/',
						'Tools' => '/technology/tools/',
						'USB' => '/technology/usb/',
						'Wearables' => '/technology/wearables/',
						'Websites' => '/technology/websites/',
						'Wireless' => '/technology/wireless/',
					),
					'Workshop' => array(
						'All' => '/workshop/',
						'Woodworking' => '/workshop/woodworking/',
						'Tools' => '/workshop/tools/',
						'Gardening' => '/workshop/gardening/',
						'Cars' => '/workshop/cars/',
						'Metalworking' => '/workshop/metalworking/',
						'Cardboard' => '/workshop/cardboard/',
						'Electric Vehicles' => '/workshop/electric-vehicles/',
						'Energy' => '/workshop/energy/',
						'Furniture' => '/workshop/furniture/',
						'Home Improvement' => '/workshop/home-improvement/',
						'Home Theater' => '/workshop/home-theater/',
						'Hydroponics' => '/workshop/hydroponics/',
						'Laser Cutting' => '/workshop/laser-cutting/',
						'Lighting' => '/workshop/lighting/',
						'Molds & Casting' => '/workshop/molds-and-casting/',
						'Motorcycles' => '/workshop/motorcycles/',
						'Organizing' => '/workshop/organizing/',
						'Pallets' => '/workshop/pallets/',
						'Repair' => '/workshop/repair/',
						'Shelves' => '/workshop/shelves/',
						'Solar' => '/workshop/solar/',
						'Workbenches' => '/workshop/workbenches/',
					),
					'Home' => array(
						'All' => '/home/',
						'Halloween' => '/home/halloween/',
						'Decorating' => '/home/decorating/',
						'Organizing' => '/home/organizing/',
						'Pets' => '/home/pets/',
						'Life Hacks' => '/home/life-hacks/',
						'Beauty' => '/home/beauty/',
						'Christmas' => '/home/christmas/',
						'Cleaning' => '/home/cleaning/',
						'Education' => '/home/education/',
						'Finances' => '/home/finances/',
						'Gardening' => '/home/gardening/',
						'Green' => '/home/green/',
						'Health' => '/home/health/',
						'Hiding Places' => '/home/hiding-places/',
						'Holidays' => '/home/holidays/',
						'Homesteading' => '/home/homesteading/',
						'Kids' => '/home/kids/',
						'Kitchen' => '/home/kitchen/',
						'Life Skills' => '/home/life-skills/',
						'Parenting' => '/home/parenting/',
						'Pest Control' => '/home/pest-control/',
						'Relationships' => '/home/relationships/',
						'Reuse' => '/home/reuse/',
						'Travel' => '/home/travel/',
					),
					'Outside' => array(
						'All' => '/outside/',
						'Bikes' => '/outside/bikes/',
						'Survival' => '/outside/survival/',
						'Backyard' => '/outside/backyard/',
						'Beach' => '/outside/beach/',
						'Birding' => '/outside/birding/',
						'Boats' => '/outside/boats/',
						'Camping' => '/outside/camping/',
						'Climbing' => '/outside/climbing/',
						'Fire' => '/outside/fire/',
						'Fishing' => '/outside/fishing/',
						'Hunting' => '/outside/hunting/',
						'Kites' => '/outside/kites/',
						'Knives' => '/outside/knives/',
						'Knots' => '/outside/knots/',
						'Paracord' => '/outside/paracord/',
						'Rockets' => '/outside/rockets/',
						'Skateboarding' => '/outside/skateboarding/',
						'Snow' => '/outside/snow/',
						'Water' => '/outside/water/',
					),
					'Food' => array(
						'All' => '/food/',
						'Dessert' => '/food/dessert/',
						'Snacks & Appetizers' => '/food/snacks-and-appetizers/',
						'Bacon' => '/food/bacon/',
						'BBQ & Grilling' => '/food/bbq-and-grilling/',
						'Beverages' => '/food/beverages/',
						'Bread' => '/food/bread/',
						'Breakfast' => '/food/breakfast/',
						'Cake' => '/food/cake/',
						'Candy' => '/food/candy/',
						'Canning & Preserves' => '/food/canning-and-preserves/',
						'Cocktails & Mocktails' => '/food/cocktails-and-mocktails/',
						'Coffee' => '/food/coffee/',
						'Cookies' => '/food/cookies/',
						'Cupcakes' => '/food/cupcakes/',
						'Homebrew' => '/food/homebrew/',
						'Main Course' => '/food/main-course/',
						'Pasta' => '/food/pasta/',
						'Pie' => '/food/pie/',
						'Pizza' => '/food/pizza/',
						'Salad' => '/food/salad/',
						'Sandwiches' => '/food/sandwiches/',
						'Soups & Stews' => '/food/soups-and-stews/',
						'Vegetarian & Vegan' => '/food/vegetarian-and-vegan/',
					),
					'Costumes' => array(
						'All' => '/costumes/',
						'Props' => '/costumes/props-and-accessories/',
						'Animals' => '/costumes/animals/',
						'Comics' => '/costumes/comics/',
						'Fantasy' => '/costumes/fantasy/',
						'For Kids' => '/costumes/for-kids/',
						'For Pets' => '/costumes/for-pets/',
						'Funny' => '/costumes/funny/',
						'Games' => '/costumes/games/',
						'Historic & Futuristic' => '/costumes/historic-and-futuristic/',
						'Makeup' => '/costumes/makeup/',
						'Masks' => '/costumes/masks/',
						'Scary' => '/costumes/scary/',
						'TV & Movies' => '/costumes/tv-and-movies/',
						'Weapons & Armor' => '/costumes/weapons-and-armor/',
					)
				),
				'title' => 'Select your category (required)',
				'defaultValue' => 'Technology'
			),
			'filter' => array(
				'name' => 'Filter',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Featured' => ' ',
					'Recent' => 'recent/',
					'Popular' => 'popular/',
					'Views' => 'views/',
					'Contest Winners' => 'winners/'
				),
				'title' => 'Select a filter',
				'defaultValue' => 'Featured'
			)
		)
	);

	private $uri;

	public function collectData() {
		// Enable the following line to get the category list (dev mode)
		// $this->listCategories();

		$this->uri = static::URI;

		switch($this->queriedContext) {
			case 'Category': $this->uri .= $this->getInput('category') . $this->getInput('filter');
		}

		$html = getSimpleHTMLDOM($this->uri)
				or returnServerError('Error loading category ' . $this->uri);

		foreach($html->find('ul.explore-covers-list li') as $cover) {
			$item = array();

			$item['uri'] = static::URI . $cover->find('a.cover-image', 0)->href;
			$item['title'] = $cover->find('.title', 0)->innertext;
			$item['author'] = $this->getCategoryAuthor($cover);
			$item['content'] = '<a href='
			. $item['uri']
			. '><img src='
			. $cover->find('a.cover-image img', 0)->src
			. '></a>';

			$image = str_replace('.RECTANGLE1', '.LARGE', $cover->find('a.cover-image img', 0)->src);
			$item['enclosures'] = [$image];

			$this->items[] = $item;
		}
	}

	public function getName() {
		if(!is_null($this->getInput('category'))
		&& !is_null($this->getInput('filter'))) {
			foreach(self::PARAMETERS[$this->queriedContext]['category']['values'] as $key => $value) {
				$subcategory = array_search($this->getInput('category'), $value);

				if($subcategory !== false)
					break;
			}

			$filter = array_search(
				$this->getInput('filter'),
				self::PARAMETERS[$this->queriedContext]['filter']['values']
			);

			return $subcategory . ' (' . $filter . ') - ' . static::NAME;
		}

		return parent::getName();
	}

	public function getURI() {
		if(!is_null($this->getInput('category'))
		&& !is_null($this->getInput('filter'))) {
			return $this->uri;
		}

		return parent::getURI();
	}

	/**
	 * Returns a list of categories for development purposes (used to build the
	 * parameters list)
	 */
	private function listCategories(){
		// Use arbitrary category to receive full list
		$html = getSimpleHTMLDOM(self::URI . '/technology/');

		foreach($html->find('.channel a') as $channel) {
			$name = html_entity_decode(trim($channel->innertext));

			// Remove unwanted entities
			$name = str_replace("'", '', $name);
			$name = str_replace('&#39;', '', $name);

			$uri = $channel->href;

			$category = explode('/', $uri)[1];

			if(!isset($categories)
			|| !array_key_exists($category, $categories)
			|| !in_array($uri, $categories[$category]))
				$categories[$category][$name] = $uri;
		}

		// Build PHP array manually
		foreach($categories as $key => $value) {
			$name = ucfirst($key);
			echo "'{$name}' => array(\n";
			echo "\t'All' => '/{$key}/',\n";
			foreach($value as $name => $uri) {
				echo "\t'{$name}' => '{$uri}',\n";
			}
			echo "),\n";
		}

		die;
	}

	/**
	 * Returns the author as anchor for a given cover.
	 */
	private function getCategoryAuthor($cover) {
		return '<a href='
		. static::URI . $cover->find('span.author a', 0)->href
		. '>'
		. $cover->find('span.author a', 0)->innertext
		. '</a>';
	}
}
