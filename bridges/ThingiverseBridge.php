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
			'queryextension' => array(
				'name' => 'Query extension',
				'type' => 'text',
				'requied' => false,
				'title' => 'Insert additional query parts here
(anything after ?search=<your search query>)',
				'exampleValue' => '&type=things&sort=newest'
			),
			'showimage' => array(
				'name' => 'Show image in content',
				'type' => 'checkbox',
				'requrired' => false,
				'title' => 'Activate to show the image in the content',
				'defaultValue' => false
			)
		)
	);

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Failed to receive ' . $this->getURI());

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

			if(!is_null($this->getInput('queryextension'))) {
				$uri .= $this->getInput('queryextension');
			}

			return $uri;
		}

		return parent::getURI();
	}

}
