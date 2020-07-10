<?php
class ReutersBridge extends BridgeAbstract {

	const MAINTAINER = 'hollowleviathan';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns news from Reuters';

	const PARAMETERS = array(
		'feed' => array(
			'name'	=> 'News feed',
			'required'	=> true,
			'exampleValue'	=> 'world',
			'title' => 'Reuters feed. World, US, Tech...'
		),
	);

	private function getJson($feedname) {
		$uri = "https://wireapi.reuters.com/v8/feed/rapp/us/tabbar/feeds/$feedname";
		$json = json_decode(getContents($uri), true);
		return $json['data'][0];
	}

	public function collectData() {
		$feed = $this->getInput('feed');
		$data = $this->getJson($feed);

		foreach ($data['wireitems'] as $wire_item) {
			if ($wire_item["wireitem_type"] == "story") {
				$item = array();
				$item['content'] = $wire_item["templates"][1]["story"]["lede"];
				$item['title'] = $wire_item["templates"][1]["story"]["hed"];
				$item['timestamp'] = $wire_item["templates"][1]["story"]["updated_at"];
				$item['uri'] = $wire_item["templates"][1]["template_action"]["url"];
				$this->items[] = $item;
			}
		}
	}
}
