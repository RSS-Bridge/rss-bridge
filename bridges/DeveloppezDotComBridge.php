<?php
class DeveloppezDotComBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "polopollo";
		$this->name = "Developpez.com Actus (FR)";
		$this->uri = "http://www.developpez.com/";
		$this->description = "Returns the 15 newest posts from DeveloppezDotCom (full text).";
		$this->update = "2016-08-09";
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
		$articleHTMLContent = $this->file_get_html($url);
		$text = $this->convert_smart_quotes($articleHTMLContent->find('div.content', 0)->innertext);
		$text = utf8_encode($text);
		return trim($text);
	}

	public function collectData(array $param){
		$rssFeed = $this->file_get_html('http://www.developpez.com/index/rss') or $this->returnError('Could not request http://www.developpez.com/index/rss', 404);
		$limit = 0;

		foreach($rssFeed->find('item') as $element) {
			if($limit < 10) {
				$item = new \Item();
				$item->title = $this->DeveloppezDotComStripCDATA($element->find('title', 0)->innertext);
				$item->uri = $this->DeveloppezDotComStripCDATA($element->find('guid', 0)->plaintext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$content = $this->DeveloppezDotComExtractContent($item->uri);
				$item->content = strlen($content) ? $content : $element->description; //In case of it is a tutorial, we just keep the original description
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 1800; // 30min
	}
}
