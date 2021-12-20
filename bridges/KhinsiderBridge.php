<?php

class KhinsiderBridge extends BridgeAbstract
{
	const MAINTAINER = 'Chouchenos';
	const NAME = 'Khinsider';
	const URI = 'https://downloads.khinsider.com/';
	const CACHE_TIMEOUT = 14400; // 4 h
	const DESCRIPTION = 'Fetch daily game OST from Khinsider';

	public function collectData()
	{
		$html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request Khinsider.');

		$dates = $html->find('#EchoTopic h3');
		foreach ($dates as $date) {
			$item = array();
			$item['uri'] = self::URI;
			$item['timestamp'] = DateTime::createFromFormat('F jS, Y', $date->plaintext)->format('U');
			$item['title'] = sprintf('OST for %s', $date->plaintext);
			$item['author'] = 'Khinsider';
			$links = $date->next_sibling()->find('a');
			$content = '<ul>';
			foreach ($links as $link) {
				$content .= sprintf('<li><a href="%s">%s</a></li>', $link->href, $link->plaintext);
			}
			$content .= '</ul>';
			$item['content'] = $content;
			$item['uid'] = $item['timestamp'];
			$item['categories'] = array('Video games', 'Music', 'OST', 'download');

			$this->items[] = $item;
		}
	}
}
