<?php
class DeveloppezDotComBridge extends BridgeAbstract{

	const MAINTAINER = "polopollo";
	const NAME = "Developpez.com Actus (FR)";
	const URI = "http://www.developpez.com/";
	const DESCRIPTION = "Returns the 15 newest posts from DeveloppezDotCom (full text).";

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
		$articleHTMLContent = $this->getSimpleHTMLDOM($url);
		$text = $this->convert_smart_quotes($articleHTMLContent->find('div.content', 0)->innertext);
		$text = utf8_encode($text);
		return trim($text);
	}

	public function collectData(){
        $rssFeed = $this->getSimpleHTMLDOM(self::URI.'index/rss')
            or $this->returnServerError('Could not request '.self::URI.'index/rss');
		$limit = 0;

		foreach($rssFeed->find('item') as $element) {
			if($limit < 10) {
				$item = array();
				$item['title'] = $this->DeveloppezDotComStripCDATA($element->find('title', 0)->innertext);
				$item['uri'] = $this->DeveloppezDotComStripCDATA($element->find('guid', 0)->plaintext);
				$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
				$content = $this->DeveloppezDotComExtractContent($item['uri']);
				$item['content'] = strlen($content) ? $content : $element->description; //In case of it is a tutorial, we just keep the original description
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 1800; // 30min
	}
}
