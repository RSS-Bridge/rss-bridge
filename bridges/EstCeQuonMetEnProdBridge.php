<?php
class EstCeQuonMetEnProdBridge extends BridgeAbstract {

	const MAINTAINER = 'ORelio';
	const NAME = 'Est-ce qu\'on met en prod aujourd\'hui ?';
	const URI = 'https://www.estcequonmetenprodaujourdhui.info/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Should we put a website in production today? (French)';

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request EstCeQuonMetEnProd: ' . self::URI);

		$item = array();
		$item['uri'] = $this->getURI() . '#' . date('Y-m-d');
		$item['title'] = $this->getName();
		$item['author'] = 'Nicolas Hoffmann';
		$item['timestamp'] = strtotime('today midnight');
		$item['content'] = str_replace(
			'src="/',
			'src="' . self::URI,
			trim(extractFromDelimiters($html->outertext, '<body role="document">', '<div id="share'))
		);

		$this->items[] = $item;
	}
}
