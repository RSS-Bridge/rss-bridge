<?php
class CommonDreamsBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "nyutag";
		$this->name = "CommonDreams Bridge";
		$this->uri = "http://www.commondreams.org/";
		$this->description = "Returns the newest articles.";
		$this->update = "2016-08-09";
	}

	private function CommonDreamsExtractContent($url) {
		$html3 = $this->file_get_html($url);
		$text = $html3->find('div[class=field--type-text-with-summary]', 0)->innertext;
		$html3->clear();
		unset ($html3);
		return $text;
	}

	public function collectData(array $param){

		function CommonDreamsUrl($string) {
			$html2 = explode(" ", $string);
			$string = $html2[2] . "/node/" . $html2[0];
			return $string;
		}

		$html = $this->file_get_html('http://www.commondreams.org/rss.xml') or $this->returnError('Could not request CommonDreams.', 404);
		$limit = 0;
		foreach($html->find('item') as $element) {
			if($limit < 4) {
				$item = new \Item();
				$item->title = $element->find('title', 0)->innertext;
				$item->uri = CommonDreamsUrl($element->find('guid', 0)->innertext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->CommonDreamsExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
