<?php
class FierPandaBridge extends BridgeAbstract {

	const MAINTAINER = 'snroki';
	const NAME = 'Fier Panda Bridge';
	const URI = 'http://www.fier-panda.fr/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns latest articles from Fier Panda.';

	public function collectData(){

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request Fier Panda.');

		defaultLinkTo($html, static::URI);

		foreach($html->find('article') as $article) {

			$item = array();

			$item['uri'] = $article->find('a', 0)->href;
			$item['title'] = $article->find('a', 0)->title;

			$this->items[] = $item;

		}

	}
}
