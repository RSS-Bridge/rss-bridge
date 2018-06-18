<?php

class AmazonBridge extends BridgeAbstract {

	const MAINTAINER = 'Alexis CHEMEL';
	const NAME = 'Amazon';
	const URI = 'https://www.amazon.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns products from Amazon search';

	const PARAMETERS = [[
		'q' => [
			'name' => 'Keyword',
			'required' => true,
		],
		'sort' => [
			'name' => 'Sort by',
			'type' => 'list',
			'required' => false,
			'values' => [
				'Relevance' => 'relevanceblender',
				'Price: Low to High' => 'price-asc-rank',
				'Price: High to Low' => 'price-desc-rank',
				'Average Customer Review' => 'review-rank',
				'Newest Arrivals' => 'date-desc-rank',
			],
			'defaultValue' => 'relevanceblender',
		],
		'tld' => [
			'name' => 'Country',
			'type' => 'list',
			'required' => true,
			'values' => [
				'Australia' => 'com.au',
				'Brazil' => 'com.br',
				'Canada' => 'ca',
				'China' => 'cn',
				'France' => 'fr',
				'Germany' => 'de',
				'India' => 'in',
				'Italy' => 'it',
				'Japan' => 'co.jp',
				'Mexico' => 'com.mx',
				'Netherlands' => 'nl',
				'Spain' => 'es',
				'United Kingdom' => 'co.uk',
				'United States' => 'com',
			],
			'defaultValue' => 'com',
		],
	]];

	public function getName(){
		if(!is_null($this->getInput('tld')) && !is_null($this->getInput('q'))) {
			return 'Amazon.'.$this->getInput('tld').': '.$this->getInput('q');
		}

		return parent::getName();
	}

	public function collectData() {

		$uri = 'https://www.amazon.'.$this->getInput('tld').'/';
		$uri .= 's/?field-keywords='.urlencode($this->getInput('q')).'&sort='.$this->getInput('sort');

		$html = getSimpleHTMLDOM($uri)
			or returnServerError('Could not request Amazon.');

		foreach($html->find('li.s-result-item') as $element) {

			$item = [];

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
