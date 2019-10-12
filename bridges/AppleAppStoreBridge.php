<?php

class AppleAppStoreBridge extends BridgeAbstract {

	const MAINTAINER = 'captn3m0';
	const NAME = 'Apple App Store';
	const URI = 'https://apps.apple.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns version updates for a specific application';

	const PARAMETERS = array(array(
		'id' => array(
			'name' => 'Application ID',
			'required' => true,
			'exampleValue' => '310633997'
		),
		'p' => array(
			'name' => 'Platform',
			'type' => 'list',
			'values' => array(
				'Web' => 'web',
				'Apple TV' => 'appletv',
				'iPad' => 'ipad',
				'iPhone' => 'iphone',
				'Mac' => 'mac',
			),
			'defaultValue' => 'iphone',
		),
		'country' => array(
			'name' => 'Store Country',
			'type' => 'list',
			'values' => array(
				'US'	=> 'US',
				'India'	=> 'IN',
				'Canada'=> 'CA'
			),
			'defaultValue' => 'US',
		),
	));

	private function makeHtmlUrl($id, $country){
		return "https://apps.apple.com/" . $country . "/app/id" . $id;
	}

	private function makeJsonUrl($id, $platform, $country){
		return "https://amp-api.apps.apple.com/v1/catalog/$country/apps/$id?platform=$platform&extend=versionHistory";
	}

	public function collectData() {

		$id = $this->getInput('id');
		$country = $this->getInput('country');
		$platform = $this->getInput('p');

		$uri = $this->makeHtmlUrl($id, $country);

		$html = getSimpleHTMLDOM($uri);

		$meta = $html->find('meta[name="web-experience-app/config/environment"]', 0);

		$json = urldecode($meta->content);

		$json = json_decode(urldecode($meta->content));

		$token = $json->MEDIA_API->token;

		$uri = $this->makeJsonUrl($id, $platform, $country);

		$headers = [
			'Authorization' => "Bearer $token",
			'Accept' => 'application/json',
		];


		$json_contents = getContents($uri, $headers);

		$json = json_decode($json_contents, true);

		$json = $json['data'][0];

		/* var_dump($json);die; */

		/* TODO: Get the platform from somewhere??? */
		/* iphone -> ios */
		$data = $json['attributes']['platformAttributes']['ios']['versionHistory'];
		$name = $json['attributes']['name'];
		$author = $json['attributes']['artistName'];

		foreach ($data as $row) {
			$item = array();

			$item['content'] = $row['releaseNotes'];
			/* TODO: Move this $name to the feed name */
			$item['title'] = $name . " - " . $row['versionDisplay'];
			$item['timestamp'] = $row['releaseDate'];
			$item['author'] = 'TODO';

			$this->items[] = $item;
		}
	}
}
