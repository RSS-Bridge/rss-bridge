<?php
class TheWhiteboardBridge extends BridgeAbstract {
	const NAME = 'The Whiteboard';
	const URI = 'https://www.the-whiteboard.com/';
	const DESCRIPTION = 'Get the latest comic from The Whiteboard';
	const MAINTAINER = 'CyberJacob';

	public function collectData() {
		$item = array();

		$html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not load The Whiteboard.');

		$image = $html->find('center', 1)->find('img', 0);
		$image->src = self::URI . '/' . $image->src;

		$item['title'] = explode("\r\n", $html->find('center', 1)->plaintext)[0];
		$item['content'] = $image;
		$item['timestamp'] = explode("\r\n", $html->find('center', 1)->plaintext)[0];

		$this->items[] = $item;
	}
}
