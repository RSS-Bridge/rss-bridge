<?php
class EtsyBridge extends BridgeAbstract {

	const NAME = 'Etsy search';
	const URI = 'https://www.etsy.com';
	const DESCRIPTION = 'Returns feeds for search results';
	const MAINTAINER = 'logmanoriginal';
	const PARAMETERS = [
		[
			'query' => [
				'name' => 'Search query',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert your search term here',
				'exampleValue' => 'Enter your search term'
			],
			'queryextension' => [
				'name' => 'Query extension',
				'type' => 'text',
				'requied' => false,
				'title' => 'Insert additional query parts here
(anything after ?search=<your search query>)',
				'exampleValue' => '&explicit=1&locationQuery=2921044'
			],
			'showimage' => [
				'name' => 'Show image in content',
				'type' => 'checkbox',
				'requrired' => false,
				'title' => 'Activate to show the image in the content',
				'defaultValue' => false
			]
		]
	];

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Failed to receive ' . $this->getURI());

		$results = $html->find('div.block-grid-item');

		foreach($results as $result) {
			// Skip banner cards (ads for categories)
			if($result->find('a.banner-card'))
				continue;

			$item = [];

			$item['title'] = $result->find('a', 0)->title;
			$item['uri'] = $result->find('a', 0)->href;
			$item['author'] = $result->find('div.card-shop-name', 0)->plaintext;

			$item['content'] = '<p>'
			. $result->find('div.card-price', 0)->plaintext
			. '</p><p>'
			. $result->find('div.card-title', 0)->plaintext
			. '</p>';

			$image = $result->find('img.placeholder', 0)->src;

			if($this->getInput('showimage')) {
				$item['content'] .= '<img src="' . $image . '">';
			}

			$item['enclosures'] = [$image];

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
