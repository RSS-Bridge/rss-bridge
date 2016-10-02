<?php
class DeveloppezDotComBridge extends FeedExpander {

	const MAINTAINER = "polopollo";
	const NAME = "Developpez.com Actus (FR)";
	const URI = "http://www.developpez.com/";
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = "Returns the 15 newest posts from DeveloppezDotCom (full text).";

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'index/rss', 15);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['content'] = $this->DeveloppezDotComExtractContent($item['uri']);
		return $item;
	}

	private function DeveloppezDotComStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	// F***ing quotes from Microsoft Word badly encoded, here was the trick:
	// http://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
	private function convert_smart_quotes($string)
	{
		$search = array(chr(145),
						chr(146),
						chr(147),
						chr(148),
						chr(151));

		$replace = array("'",
							"'",
							'"',
							'"',
							'-');

		return str_replace($search, $replace, $string);
	}

	private function DeveloppezDotComExtractContent($url) {
		$articleHTMLContent = getSimpleHTMLDOMCached($url);
		$text = $this->convert_smart_quotes($articleHTMLContent->find('div.content', 0)->innertext);
		$text = utf8_encode($text);
		return trim($text);
	}
}
