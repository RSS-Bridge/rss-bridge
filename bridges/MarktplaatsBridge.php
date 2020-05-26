<?php

class MarktplaatsBridge extends BridgeAbstract {
	const NAME = 'Marktplaats';
	const URI = 'https://marktplaats.nl';
	const DESCRIPTION = 'Read search queries from marktplaats';
	const PARAMETERS = array(
		'Search' => array(
			'n' => array(
				'name' => 'query',
				'type' => 'text',
				'required' => true,
				'title' => 'The search string for marktplaats',
			)
		)
	);
	const CACHE_TIMEOUT = 900;

	public function collectData() {
	$url = 'https://www.marktplaats.nl/lrp/api/search?query=' . urlencode($this->getInput('n'));
		$jsonString = getSimpleHTMLDOM($url, 900) or returnServerError('No contents received!');
		$jsonObj = json_decode($jsonString);
		foreach($jsonObj->listings as $listing) {
			$item = array();
			$item['uri'] = 'https://marktplaats.nl' . $listing->vipUrl;
			$item['title'] = $listing->title;
			$item['timestamp'] = $listing->date;
			$item['author'] = $listing->sellerInformation->sellerName;
			$item['content'] = $listing->description . "\n\n\nSellerID: " . $listing->sellerInformation->sellerId;
			$item['enclosures'] = $listing->imageUrls;
			$item['categories'] = $listing->verticals;
			$item['uid'] = $listing->itemId;
			$this->items[] = $item;
		}

	}
}
