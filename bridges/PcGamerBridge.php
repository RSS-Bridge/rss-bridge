<?php
class PcGamerBridge extends BridgeAbstract
{
	const NAME = 'PC Gamer';
	const URI = 'https://www.pcgamer.com/';
	const DESCRIPTION = 'PC Gamer Most Read Stories';
	const MAINTAINER = 'mdemoss';

	public function collectData()
	{
		$html = getSimpleHTMLDOMCached($this->getURI(), 300);
		$stories = $html->find('div#popularcontent li.most-popular-item');
		foreach ($stories as $element) {
			$item['uri'] = $element->find('a', 0)->href;
			$articleHtml = getSimpleHTMLDOMCached($item['uri']);
			$item['title'] = $articleHtml->find('meta[property=og:title]', 0)->content;
			$item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);
			$item['content'] = $articleHtml->find('meta[property=og:description]', 0)->content;
			$item['author'] = $articleHtml->find('meta[name=parsely-author', 0)->content;
			$this->items[] = $item;
		}
	}
}
