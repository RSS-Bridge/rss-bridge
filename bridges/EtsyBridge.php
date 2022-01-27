<?php
class EtsyBridge extends BridgeAbstract {

	const NAME = 'Etsy search';
	const URI = 'https://www.etsy.com';
	const DESCRIPTION = 'Returns feeds for search results';
	const MAINTAINER = 'logmanoriginal';
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
				'required' => false,
				'title' => 'Insert additional query parts here
(anything after ?search=<your search query>)',
				'exampleValue' => '&explicit=1&locationQuery=2921044'
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

		$results = $html->find('li.block-grid-item');

		foreach($results as $result) {
			// Skip banner cards (ads for categories)
			if($result->find('span.ad-indicator'))
				continue;

			$item = array();

			$item['title'] = $result->find('a', 0)->title;
			$item['uri'] = $result->find('a', 0)->href;
			$item['author'] = $result->find('p.text-gray-lighter', 0)->plaintext;

			$item['content'] = '<p>'
			. $result->find('span.currency-value', 0)->plaintext . ' '
			. $result->find('span.currency-symbol', 0)->plaintext
			. '</p><p>'
			. $result->find('a', 0)->title
			. '</p>';

			$image = $result->find('img.display-block', 0)->src;

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
