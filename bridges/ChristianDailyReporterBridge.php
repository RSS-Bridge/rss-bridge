<?php
class ChristianDailyReporterBridge extends BridgeAbstract {

	const MAINTAINER = 'rogerdc';
	const NAME = 'Christian Daily Reporter Unofficial RSS';
	const URI = 'https://www.christiandailyreporter.com/';
	const DESCRIPTION = 'The Unofficial Christian Daily Reporter RSS';
	// const CACHE_TIMEOUT = 86400; // 1 day

	public function getIcon() {
		return self::URI . 'images/cdrfavicon.png';
	}

	public function collectData() {
		$uri = 'https://www.christiandailyreporter.com/';

		$html = getSimpleHTMLDOM($uri)
			or returnServerError('Could not request Christian Daily Reporter.');
		foreach($html->find('div.top p a,div.column p a') as $element) {
			$item = array();
			// Title
			$item['title'] = $element->innertext;
			// URL
			$item['uri'] = $element->href;
			$this->items[] = $item;
		}
	}
}
