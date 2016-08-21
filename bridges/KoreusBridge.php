<?php
class KoreusBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "pit-fgfjiudghdf";
		$this->name = "Koreus";
		$this->uri = "http://www.koreus.com/";
		$this->description = "Returns the 5 newest posts from Koreus (full text)";
	}

	private function KoreusStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	private function KoreusExtractContent($url) {
		$html2 = $this->getSimpleHTMLDOM($url);
		$text = $html2->find('p[class=itemText]', 0)->innertext;
		$text = utf8_encode(preg_replace('/(Sur le m.+?)+$/i','',$text));
		return $text;
	}

	public function collectData(array $param){
		$html = $this->getSimpleHTMLDOM('http://feeds.feedburner.com/Koreus-articles') or $this->returnServerError('Could not request Koreus.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = $this->KoreusStripCDATA($element->find('title', 0)->innertext);
				$item->uri = $this->KoreusStripCDATA($element->find('guid', 0)->plaintext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->KoreusExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
