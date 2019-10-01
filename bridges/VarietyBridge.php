<?php
class VarietyBridge extends FeedExpander {

	const MAINTAINER = 'IceWreck';
	const NAME = 'Variety Bridge';
	const URI = 'https://variety.com';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'RSS feed for Variety';

	public function collectData(){
		$this->collectExpandableDatas('http://feeds.feedburner.com/variety/headlines', 15);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		// $articlePage gets the entire page's contents
		$articlePage = getSimpleHTMLDOM($newsItem->link);
		$article = $articlePage->find('div.c-featured-media', 0);
		$article = $article . $articlePage->find('.c-content', 0);
		$item['content'] = $article;
		// I've left the script tags alone because some feed readers support them,
		// even tho they look ugly in rssbridge's html view
		return $item;
	}
}
