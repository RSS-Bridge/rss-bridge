<?php
class SpottschauBridge extends BridgeAbstract {
	const NAME = 'Härringers Spottschau Bridge';
	const URI = 'https://spottschau.com/';
	const DESCRIPTION = 'Der Fußball-Comic';
	const MAINTAINER = 'sal0max';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600; // 1 hour

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI);

		$item = array();
		$item['uri'] = urljoin(self::URI, $html->find('div.strip>a', 0)->attr['href']);
		$item['title'] = $html->find('div.text>h2', 0)->innertext;
		$item['timestamp'] = $item['title'];

		$image = $html->find('div.strip>a>img', 0);
		$imageUrl = urljoin(self::URI, $image->attr['src']);
		$imageAlt = $image->attr['alt'];

		$item['content'] = <<<EOD
<img src="{$imageUrl}" alt="{$imageAlt}"/>
<br/>
EOD;
		$this->items[] = $item;
	}
}
