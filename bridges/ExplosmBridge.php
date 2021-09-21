<?php
class ExplosmBridge extends FeedExpander {

	const MAINTAINER = 'bockiii';
	const NAME = 'Explosm Bridge';
	const URI = 'https://www.explosm.net/';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'Returns the last 5 comics';

	public function collectData(){
		$this->collectExpandableDatas('https://feeds.feedburner.com/Explosm');
	}

	protected function parseItem($feedItem){
		$item = parent::parseItem($feedItem);
		$comicpage = getSimpleHTMLDOM($item['uri']);
		$image = $comicpage->find('div[id=comic-wrap]', 0)->find('img', 0)->getAttribute('src');
		$item['content'] = '<img src="https:' . $image . '" />';

		return $item;
	}
}
