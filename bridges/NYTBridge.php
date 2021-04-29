<?php
class NYTBridge extends FeedExpander {

	const MAINTAINER = 'IceWreck';
	const NAME = 'New York Times Bridge';
	const URI = 'https://www.nytimes.com/';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'RSS feed for the New York Times';

	public function collectData(){
		$this->collectExpandableDatas('https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml', 15);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		// $articlePage gets the entire page's contents
		$articlePage = getSimpleHTMLDOM($newsItem->link);
		// figure contain's the main article image
		$article = $articlePage->find('figure', 0);
		// p > css-exrw3m has the actual article
		foreach($articlePage->find('p.css-exrw3m') as $element)
			$article = $article . $element;
		$item['content'] = $article;
		return $item;
	}
}
