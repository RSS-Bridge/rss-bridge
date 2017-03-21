<?php
class RainbowSixSiegeBridge extends BridgeAbstract {

	const MAINTAINER = 'corenting';
	const NAME = 'Rainbow Six Siege Blog';
	const URI = 'https://rainbow6.ubisoft.com/siege/en-us/news/';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Latest articles from the Rainbow Six Siege blog';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . 'index.aspx')
			or returnServerError('Error while downloading the website content');

		$list = $html->find('.comdev');

		// Start at index 2 to remove highlighted articles
		for($i = 2; $i < count($list); $i++){
			$article = $list[$i];

			$item = array();

			$uri = $article->find('h3 a', 0)->href;
			$uri = 'https://rainbow6.ubisoft.com' . $uri;
			$item['uri'] = $uri;
			$item['title'] = $article->find('h3 a', 0)->plaintext;
			$item['content'] = $article->find('a', 0)->innertext . '<br />' . $article->find('strong', 0)->plaintext;
			$item['timestamp'] = strtotime($article->find('p', 0)->plaintext);

			$this->items[] = $item;
		}
	}
}
