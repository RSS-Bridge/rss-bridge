<?php
class NextInpactBridge extends FeedExpander {

	const MAINTAINER = "qwertygc";
	const NAME = "NextInpact Bridge";
	const URI = "http://www.nextinpact.com/";
	const DESCRIPTION = "Returns the newest articles.";

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'rss/news.xml', 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['content'] = $this->ExtractContent($item['uri']);
		return $item;
	}

	private function ExtractContent($url) {
		$html2 = getSimpleHTMLDOMCached($url);
		$text = '<p><em>'.$html2->find('span.sub_title', 0)->innertext.'</em></p>'
			.'<p><img src="'.$html2->find('div.container_main_image_article', 0)->find('img.dedicated',0)->src.'" alt="-" /></p>'
			.'<div>'.$html2->find('div[itemprop=articleBody]', 0)->innertext.'</div>';
		$premium_article = $html2->find('h2.title_reserve_article', 0);
		if (is_object($premium_article))
			$text = $text.'<p><em>'.$premium_article->innertext.'</em></p>';
		return $text;
	}
}
