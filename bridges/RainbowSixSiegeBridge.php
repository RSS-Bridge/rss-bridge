<?php
class RainbowSixSiegeBridge extends BridgeAbstract {

	const MAINTAINER = 'corenting';
	const NAME = 'Rainbow Six Siege News';
	const URI = 'https://www.ubisoft.com/en-us/game/rainbow-six/siege/news-updates';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Latest news about Rainbow Six Siege';

	public function getIcon() {
		return 'https://static-dm.akamaized.net/siege/prod/favicon-144x144.png';
	}

	public function collectData(){
		$dlUrl = 'https://www.ubisoft.com/api/updates/items?locale=en-us&categoriesFilter=all';
		$dlUrl = $dlUrl . '&limit=6&mediaFilter=news&skip=0&startIndex=undefined&tags=BR-rainbow-six%20GA-siege';
		$jsonString = getContents($dlUrl) or returnServerError('Error while downloading the website content');

		$json = json_decode($jsonString, true);
		$json = $json['items'];

		// Start at index 2 to remove highlighted articles
		for($i = 0; $i < count($json); $i++) {
			$jsonItem = $json[$i];

			$uri = 'https://www.ubisoft.com/en-us/game/rainbow-six/siege';
			$uri = $uri . $jsonItem['button']['buttonUrl'];

			$thumbnail = '<img src="' . $jsonItem['thumbnail']['url'] . '" alt="Thumbnail">';
			$content = $thumbnail . '<br />' . $jsonItem['content'];

			// Line breaks
			$content = preg_replace("/\r\n|\r|\n/", '<br/>', $content);

			$item = array();
			$item['uri'] = $uri;
			$item['id'] = $jsonItem['id'];
			$item['title'] = $jsonItem['title'];
			$item['content'] = markdownToHtml($content);
			$item['timestamp'] = strtotime($jsonItem['date']);

			$this->items[] = $item;
		}
	}
}
