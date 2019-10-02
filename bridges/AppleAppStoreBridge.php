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
			'defaultValue' => '310633997'
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
			'defaultValue' => 'web',
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

	private function makeHtmlUrl($id){
	return "https://apps.apple.com/in/app/whatsapp-messenger/id$id";
	}

	private function makeJsonUrl($id, $platform = 'iphone', $country = 'US'){
		return "https://amp-api.apps.apple.com/v1/catalog/$country/apps/$id?platform=$platform&extend=versionHistory";
	}

	public function collectData() {

		$id = $this->getInput('id');
		$country = $this->getInput('country');
		$platform = $this->getInput('p');

		$uri = $this->makeHtmlUrl($id);

		$html = getSimpleHTMLDOM($uri);

		$meta = $html->find('meta[name="web-experience-app/config/environment"]', 0);

		$json = urldecode($meta->content);

		$json = json_decode(urldecode($meta->content));

		$token = $json->MEDIA_API->token;

		$uri = $this->makeJsonUrl($id, $platform, $country);

		$headers = [
			'Authorization' => "Bearer $token",
			'Accept'		=> 'application/json',
			'User-Agent'	=> 'rss-bridge',
		];

		// var_dump($uri, $headers);die;

		$json = json_decode(getSimpleHTMLDOM($uri, $headers), true);

		$json = $json['JSON']['data'];

		$data = $json['attributes']['platformAttributes'][$platform]['versionHistory'];
		$name = $json['attributes']['name'];
		$author = $json['attributes']['artistName'];

		foreach ($data as $row){
			$item = [
				'content' => $row['releaseNotes'],
				'title'   => $name " - " $row['versionDisplay'],
				'timestamp' => $row['releaseDate'],
				'author'	=> 
			];
		}
	}
}
