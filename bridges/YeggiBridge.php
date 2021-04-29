<?php
class YeggiBridge extends BridgeAbstract {

	const NAME = 'Yeggi Search';
	const URI = 'https://www.yeggi.com';
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
					'Best match' => '0',
					'Popular' => '1',
					'Latest' => '2',
				),
				'defaultValue' => 'newest'
			),
			'show' => array(
				'name' => 'Show',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'All' => '0',
					'Free' => '1',
					'For sale' => '2',
				),
				'defaultValue' => 'all'
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
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Failed to receive ' . $this->getURI());

		$results = $html->find('div.item_1_A');

		foreach($results as $result) {

			$item = array();
			$title = $result->find('.item_3_B_2', 0)->plaintext;
			$explodeTitle = explode('&nbsp;  ', $title);
			if(count($explodeTitle) == 2) {
				$item['title'] = $explodeTitle[1];
			} else {
				$item['title'] = $explodeTitle[0];
			}
			$item['uri'] = self::URI . $result->find('a', 0)->href;
			$item['author'] = 'Yeggi';
			$item['content'] = '';
			$item['uid'] = hash('md5', $item['title']);

			$image = $result->find('img', 0)->src;

			if($this->getInput('showimage')) {
				$item['content'] .= '<img src="' . $image . '">';
			}

			$item['enclosures'] = array($image);

			$this->items[] = $item;
		}
	}

	public function getURI(){
		if(!is_null($this->getInput('query'))) {
			$uri = self::URI . '/q/' . urlencode($this->getInput('query')) . '/';
			$uri .= '?o_f=' . $this->getInput('show');
			$uri .= '&o_s=' . $this->getInput('sortby');

			return $uri;
		}

		return parent::getURI();
	}
}
