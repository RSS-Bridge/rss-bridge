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

	const PLATFORM_MAPPING = array(
		'iphone' => 'ios'
	);

	private function makeHtmlUrl($id, $country){
		return "https://apps.apple.com/" . $country . "/app/id" . $id;
	}

	private function makeJsonUrl($id, $platform, $country){
		return "https://amp-api.apps.apple.com/v1/catalog/$country/apps/$id?platform=$platform&extend=versionHistory";
	}

	private function getJWTToken($id, $platform, $country){
		$uri = $this->makeHtmlUrl($id, $country);

		$html = getSimpleHTMLDOM($uri, 3600);

		$meta = $html->find('meta[name="web-experience-app/config/environment"]', 0);

		$json = urldecode($meta->content);

		$json = json_decode($json);

		return $json->MEDIA_API->token;
	}

	private function getAppData($id, $platform, $country, $token){
		$uri = $this->makeJsonUrl($id, $platform, $country);

		$headers = [
			"Authorization: Bearer $token",
		];

		$json = json_decode(getSimpleHTMLDOM($uri, $headers), true);

		return $json['data'][0];
	}

	/**
	 * Parses the version history from the data received
	 * @return array list of versions with details on each element
	 */
	private function getVersionHistory($data, $platform){
		$os = self::PLATFORM_MAPPING[$platform];
		return $data['attributes']['platformAttributes'][$os]['versionHistory'];
	}

	public function collectData() {
		$id = $this->getInput('id');
		$country = $this->getInput('country');
		$platform = $this->getInput('p');

		$token = $this->getJWTToken($id, $platform, $country);
		$data = $this->getAppData($id, $platform, $country, $token);
		$versionHistory = $this->getVersionHistory($data, $platform);

		$name = $data['attributes']['name'];
		$author = $data['attributes']['artistName'];

		foreach ($versionHistory as $row) {
			$item = array();

			$item['content'] = nl2br($row['releaseNotes']);
			$item['title'] = $name . " - " . $row['versionDisplay'];
			$item['timestamp'] = $row['releaseDate'];
			$item['author'] = $author;

			$item['uri'] = $this->makeHtmlUrl($id, $country);

			$this->items[] = $item;
		}
	}
}
