<?php

class AmazonBridge extends BridgeAbstract {

	const MAINTAINER = "Alexis CHEMEL";
	const NAME = "Amazon";
	const URI = "https://www.amazon.fr/";
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = "Returns products from Amazon search";

	const PARAMETERS = array(array(
		'q' => array(
			'name' => 'Keyword',
			'required' => true,
		),
		'sort' => array(
			'name' => 'Sort by',
			'type' => 'list',
			'required' => false,
			'values' => array(
				'Relevance' => 'relevanceblender',
				'Popularity' => 'popularity-rank',
				'Price : in ascending order' => 'price-asc-rank',
				'Price : in descending order' => 'price-desc-rank',
				'Average rating' => 'review-rank',
				'Newest item' => 'date-desc-rank',
			),
			'defaultValue' => 'popularity-rank',
		)
	));

	public function getName(){

		return 'Amazon - '.$this->getInput('q');
	}

	public function collectData() {

		$url = self::URI.'s/?field-keywords='.urlencode($this->getInput('q')).'&sort='.$this->getInput('sort');

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request Amazon.');

		foreach($html->find('li.s-result-item') as $element) {

			$item = array();

			// Title
			$title = $element->find('h2', 0);

			$item['title'] = html_entity_decode($title->innertext, ENT_QUOTES);

			// Url
			$uri = $title->parent()->getAttribute('href');
			$uri = substr($uri, 0, strrpos($uri, '/'));

			$item['uri'] = substr($uri, 0, strrpos($uri, '/'));

			// Content
			$image = $element->find('img', 0);
			$price = $element->find('span.s-price', 0);
			$price = ($price) ? $price->innertext : '';

			$item['content'] = '<img src="'.$image->getAttribute('src').'" /><br />'.$price;

			$this->items[] = $item;
		}
	}
}
