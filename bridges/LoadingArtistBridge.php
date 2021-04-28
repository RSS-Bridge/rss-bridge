<?php
class LoadingArtistBridge extends FeedExpander {

	const MAINTAINER = 'bockiii';
	const NAME = 'Loading Artist Bridge';
	const URI = 'https://www.loadingartist.com/';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'Returns the last 10 comics';

	public function collectData(){
		$this->collectExpandableDatas(static::URI . 'feed');
	}

    protected function parseItem($feedItem){
        $item = parent::parseItem($feedItem);
        $item['content'] = str_replace('width="150" height="150" ','',$item['content']);
        $item['content'] = str_replace('-150x150','',$item['content']);
    
        return $item;
    }
}
