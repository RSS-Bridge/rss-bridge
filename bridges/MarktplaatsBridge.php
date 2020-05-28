<?php

class MarktplaatsBridge extends BridgeAbstract {
	const NAME = 'Marktplaats';
	const URI = 'https://marktplaats.nl';
	const DESCRIPTION = 'Read search queries from marktplaats.nl';
	const PARAMETERS = array(
		'Search' => array(
			'q' => array(
				'name' => 'query',
				'type' => 'text',
				'required' => true,
				'title' => 'The search string for marktplaats',
			),
			'z' => array(
				'name' => 'zipcode',
				'type' => 'text',
				'required' => false,
				'title' => 'Zip code for location limited searches',
			),
			'd' => array(
				'name' => 'distance',
				'type' => 'number',
				'required' => false,
				'title' => 'The distance in meters from the zipcode',
			),
			'f' => array(
				'name' => 'priceFrom',
				'type' => 'number',
				'required' => false,
				'title' => 'The minimal price in cents',
			),
			't' => array(
				'name' => 'priceTo',
				'type' => 'number',
				'required' => false,
				'title' => 'The maximal price in cents',
			),
			's' => array(
				'name' => 'showGlobal',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Include result with negative distance',
			),
			'i' => array(
				'name' => 'includeImage',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Include the image at the end of the content',
			),
			'r' => array(
				'name' => 'includeRaw',
				'type' => 'checkbox',
				'required' => false,
				'title' => 'Include the raw data behind the content',
			)
		)
	);
	const CACHE_TIMEOUT = 900;

	public function collectData() {
		$query = '';
		$excludeGlobal = false;
		if(!is_null($this->getInput('z')) && !is_null($this->getInput('d'))) {
			$query = '&postcode=' . $this->getInput('z') . '&distanceMeters=' . $this->getInput('d');
		}
		if(!is_null($this->getInput('f'))) {
			$query .= '&PriceCentsFrom=' . $this->getInput('f');
		}
		if(!is_null($this->getInput('t'))) {
			$query .= '&PriceCentsTo=' . $this->getInput('t');
		}
		if(!is_null($this->getInput('s'))) {
			if(!$this->getInput('s')) {
				$excludeGlobal = true;
			}
		}
		$url = 'https://www.marktplaats.nl/lrp/api/search?query=' . urlencode($this->getInput('q')) . $query;
		$jsonString = getSimpleHTMLDOM($url, 900) or returnServerError('No contents received!');
		$jsonObj = json_decode($jsonString);
		foreach($jsonObj->listings as $listing) {
			if(!$excludeGlobal || $listing->location->distanceMeters >= 0) {
				$item = array();
				$item['uri'] = 'https://marktplaats.nl' . $listing->vipUrl;
				$item['title'] = $listing->title;
				$item['timestamp'] = $listing->date;
				$item['author'] = $listing->sellerInformation->sellerName;
				$item['content'] = $listing->description;
				$item['enclosures'] = $listing->imageUrls;
				$item['categories'] = $listing->verticals;
				$item['uid'] = $listing->itemId;
				if(!is_null($this->getInput('i')) && !empty($listing->imageUrls)) {
					if($this->getInput('i')) {
						if(is_array($listing->imageUrls)) {
							foreach($listing->imageUrls as $imgurl) {
								$item['content'] .= "<br />\n<img src='" . $imgurl . "' />";
							}
						} else {
							$item['content'] .= "<br>\n<img src='" . $listing->imageUrls . "' />";
						}
					}
				}
				if(!is_null($this->getInput('r'))) {
					if($this->getInput('r')) {
						$item['content'] .= "<br />\n<br />\n<br />\n" . json_encode($listing);
					}
				}
				$this->items[] = $item;
			}
		}
	}
}
