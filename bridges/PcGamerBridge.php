<?php
class PcGamerBridge extends BridgeAbstract
{
	const NAME = 'PC Gamer';
	const URI = 'https://www.pcgamer.com/';
	const DESCRIPTION = 'PC Gamer is your source for exclusive reviews, demos, updates and news on all your favorite PC gaming franchises.';
	const MAINTAINER = 'IceWreck, mdemoss';

	public function collectData()
	{
		$html = getSimpleHTMLDOMCached($this->getURI(), 300);
		$stories = $html->find('a.article-link');
		foreach ($stories as $element) {
			$item = array();
			$item['uri'] = $element->href;
			$articleHtml = getSimpleHTMLDOMCached($item['uri']);
			
			// Relying on meta tags ought to be more reliable.
			$item['title'] = $articleHtml->find('meta[name=parsely-title]', 0)->content;
			$item['content'] = html_entity_decode($articleHtml->find('meta[name=description]', 0)->content);
			$item['author'] = $articleHtml->find('meta[name=parsely-author]', 0)->content;
			$item['enclosures'][] = $articleHtml->find('meta[name=parsely-image-url]', 0)->content;
			$item['tags'] = $articleHtml->find('meta[name=parsely-tags]', 0)->content;
			$item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);
			$this->items[] = $item;
		}
	}
}
