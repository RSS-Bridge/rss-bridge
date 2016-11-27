<?php
class Rue89Bridge extends FeedExpander {

	const MAINTAINER = "pit-fgfjiudghdf";
	const NAME = "Rue89";
	const URI = "http://rue89.nouvelobs.com/";
	const DESCRIPTION = "Returns the 5 newest posts from Rue89 (full text)";

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$url = "http://api.rue89.nouvelobs.com/export/mobile2/node/" . str_replace(" ", "", substr($item['uri'], -8)) . "/full";
		$datas = json_decode(getContents($url), true);
		$item['content'] = $datas['node']['body'];

		return $item;
	}

    public function collectData(){
		$this->collectExpandableDatas('http://api.rue89.nouvelobs.com/feed');
    }
}
