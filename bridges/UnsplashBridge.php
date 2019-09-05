<?php
class UnsplashBridge extends BridgeAbstract {

	const MAINTAINER = 'nel50n';
	const NAME = 'Unsplash Bridge';
	const URI = 'https://unsplash.com/';
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = 'Returns the latests photos from Unsplash';

	const PARAMETERS = array( array(
		'm' => array(
			'name' => 'Max number of photos',
			'type' => 'number',
			'defaultValue' => 20
		),
		'w' => array(
			'name' => 'Width',
			'exampleValue' => '1920, 1680, …',
			'defaultValue' => '1920'
		),
		'q' => array(
			'name' => 'JPEG quality',
			'type' => 'number',
			'defaultValue' => 75
		)
	));

	public function collectData(){
		$width = $this->getInput('w');
		$max = $this->getInput('m');
		$quality = $this->getInput('q');

		$api_response = getContents('https://unsplash.com/napi/photos?page=1&per_page=' . $max)
			or returnServerError('Could not request Unsplash API.');
		$json = json_decode($api_response, true);

		foreach ($json as $json_item) {
			$item = array();

			// Get image URI
			$uri = $json_item['urls']['regular'] . '.jpg'; // '.jpg' only for format hint
			$uri = str_replace('q=80', 'q=' . $quality, $uri);
			$uri = str_replace('w=1080', 'w=' . $width, $uri);
			$item['uri'] = $uri;

			// Get title from description
			if (is_null($json_item['alt_description'])) {
				if (is_null($json_item['description'])) {
					$item['title'] = 'Unsplash picture from ' . $json_item['user']['name'];
				} else {
					$item['title'] = $json_item['description'];
				}
			} else {
				$item['title'] = $json_item['alt_description'];
			}

			$item['timestamp'] = time();
			$item['content'] = $item['title']
				. '<br><a href="'
				. $item['uri']
				. '"><img src="'
				. $json_item['urls']['thumb']
				. '" /></a>';

			$this->items[] = $item;
		}
	}
}
