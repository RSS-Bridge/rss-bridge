<?php
class CADBridge extends FeedExpander {
	const MAINTAINER = "nyutag";
	const NAME = "CAD Bridge";
	const URI = "http://www.cad-comic.com/";
	const CACHE_TIMEOUT = 7200; //2h
	const DESCRIPTION = "Returns the newest articles.";

	public function collectData(){
		$this->collectExpandableDatas('http://cdn2.cad-comic.com/rss.xml', 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['content'] = $this->CADExtractContent($item['uri']);
		return $item;
	}

	private function CADExtractContent($url) {
		$html3 = getSimpleHTMLDOMCached($url);

		// The request might fail due to missing https support or wrong URL
		if($html3 == false)
			return 'Daily comic not released yet';

		$htmlpart = explode("/", $url);

		switch ($htmlpart[3]){
			case 'cad':
				preg_match_all("/http:\/\/cdn2\.cad-comic\.com\/comics\/cad-\S*png/", $html3, $url2);
				break;
			case 'sillies':
				preg_match_all("/http:\/\/cdn2\.cad-comic\.com\/comics\/sillies-\S*gif/", $html3, $url2);
				break;
			default:
				return 'Daily comic not released yet';
		}
		$img = implode ($url2[0]);
		$html3->clear();
		unset ($html3);
		if ($img == '')
			return 'Daily comic not released yet';
		return '<img src="'.$img.'"/>';
	}
}
?>
