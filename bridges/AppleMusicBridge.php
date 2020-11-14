<?php

class AppleMusicBridge extends BridgeAbstract {
	const NAME = 'Apple Music';
	const URI = 'https://www.apple.com';
	const DESCRIPTION = 'Fetches the latest releases from an artist';
	const MAINTAINER = 'Limero';
	const PARAMETERS = array(array(
		'url' => array(
			'name' => 'Artist URL',
			'exampleValue' => 'https://itunes.apple.com/us/artist/dunderpatrullen/329796274',
			'required' => true,
		),
		'imgSize' => array(
			'name' => 'Image size for thumbnails (in px)',
			'type' => 'number',
			'defaultValue' => 512,
			'required' => true,
		)
	));
	const CACHE_TIMEOUT = 21600; // 6 hours

	private $title;

	public function collectData() {
		$url = $this->getInput('url');
		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request: ' . $url);

		$imgSize = $this->getInput('imgSize');

		$this->title = $html->find('title', 0)->innertext;

		// Grab the json data from the page
		$html = $html->find('script[id=shoebox-ember-data-store]', 0);
		$html = strstr($html, '{');
		$html = substr($html, 0, -9);
		$json = json_decode($html);

		// Loop through each object
		foreach ($json->included as $obj) {
			if ($obj->type === 'lockup/album') {
				$this->items[] = array(
					'title' => $obj->attributes->artistName . ' - ' . $obj->attributes->name,
					'uri' => $obj->attributes->url,
					'timestamp' => $obj->attributes->releaseDate,
					'enclosures' => $obj->relationships->artwork->data->id,
				);
			} elseif ($obj->type === 'image') {
				$images[$obj->id] = $obj->attributes->url;
			}
		}

		// Add the images to each item
		foreach ($this->items as &$item) {
			$item['enclosures'] = array(
				str_replace('{w}x{h}bb.{f}', $imgSize . 'x0w.jpg', $images[$item['enclosures']]),
			);
		}

		// Sort the order to put the latest albums first
		usort($this->items, function($a, $b){
			return $a['timestamp'] < $b['timestamp'];
		});
	}

	public function getName() {
		return $this->title ?: parent::getName();
	}
}
