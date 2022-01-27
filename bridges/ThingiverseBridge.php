<?php
class ThingiverseBridge extends BridgeAbstract {

	const NAME = 'Thingiverse Search';
	const URI = 'https://thingiverse.com';
	const DESCRIPTION = 'Returns feeds for search results';
	const MAINTAINER = 'AntoineTurmel';
	const PARAMETERS = array(
		array(
			'query' => array(
				'name' => 'Search query',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert your search term here',
				'exampleValue' => 'Enter your search term'
			),
			'sortby' => array(
				'name' => 'Sort by',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'Relevant' => 'relevant',
					'Text' => 'text',
					'Popular' => 'popular',
					'# of Makes' => 'makes',
					'Newest' => 'newest',
				),
				'defaultValue' => 'newest'
			),
			'category' => array(
				'name' => 'Category',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'Any' => '',
					'3D Printing' => '73',
					'Art' => '63',
					'Fashion' => '64',
					'Gadgets' => '65',
					'Hobby' => '66',
					'Household' => '67',
					'Learning' => '69',
					'Models' => '70',
					'Tools' => '71',
					'Toys &amp; Games' => '72',
					'2D Art' => '144',
					'Art Tools' => '75',
					'Coins &amp; Badges' => '143',
					'Interactive Art' => '78',
					'Math Art' => '79',
					'Scans &amp; Replicas' => '145',
					'Sculptures' => '80',
					'Signs &amp; Logos' => '76',
					'Accessories' => '81',
					'Bracelets' => '82',
					'Costume' => '142',
					'Earrings' => '139',
					'Glasses' => '83',
					'Jewelry' => '84',
					'Keychains' => '130',
					'Rings' => '85',
					'Audio' => '141',
					'Camera' => '86',
					'Computer' => '87',
					'Mobile Phone' => '88',
					'Tablet' => '90',
					'Video Games' => '91',
					'Automotive' => '155',
					'DIY' => '93',
					'Electronics' => '92',
					'Music' => '94',
					'R/C Vehicles' => '95',
					'Robotics' => '96',
					'Sport &amp; Outdoors' => '140',
					'Bathroom' => '147',
					'Containers' => '146',
					'Decor' => '97',
					'Household Supplies' => '99',
					'Kitchen &amp; Dining' => '100',
					'Office' => '101',
					'Organization' => '102',
					'Outdoor &amp; Garden' => '98',
					'Pets' => '103',
					'Replacement Parts' => '153',
					'Biology' => '106',
					'Engineering' => '104',
					'Math' => '105',
					'Physics &amp; Astronomy' => '148',
					'Animals' => '107',
					'Buildings &amp; Structures' => '108',
					'Creatures' => '109',
					'Food &amp; Drink' => '110',
					'Model Furniture' => '111',
					'Model Robots' => '115',
					'People' => '112',
					'Props' => '114',
					'Vehicles' => '116',
					'Hand Tools' => '118',
					'Machine Tools' => '117',
					'Parts' => '119',
					'Tool Holders &amp; Boxes' => '120',
					'Chess' => '151',
					'Construction Toys' => '121',
					'Dice' => '122',
					'Games' => '123',
					'Mechanical Toys' => '124',
					'Playsets' => '113',
					'Puzzles' => '125',
					'Toy &amp; Game Accessories' => '149',
					'3D Printer Accessories' => '127',
					'3D Printer Extruders' => '152',
					'3D Printer Parts' => '128',
					'3D Printers' => '126',
					'3D Printing Tests' => '129',
				)
			),
			'showimage' => array(
				'name' => 'Show image in content',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Activate to show the image in the content',
				'defaultValue' => 'checked'
			)
		)
	);

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI());

		$results = $html->find('div.thing-card');

		foreach($results as $result) {

			$item = array();

			$item['title'] = $result->find('span.ellipsis', 0);
			$item['uri'] = self::URI . $result->find('a', 1)->href;
			$item['author'] = $result->find('span.item-creator', 0);
			$item['content'] = '';

			$image = $result->find('img.card-img', 0)->src;

			if($this->getInput('showimage')) {
				$item['content'] .= '<img src="' . $image . '">';
			}

			$item['enclosures'] = array($image);

			$this->items[] = $item;
		}
	}

	public function getURI(){
		if(!is_null($this->getInput('query'))) {
			$uri = self::URI . '/search?q=' . urlencode($this->getInput('query'));
			$uri .= '&sort=' . $this->getInput('sortby');
			$uri .= '&category_id=' . $this->getInput('category');

			return $uri;
		}

		return parent::getURI();
	}
}
