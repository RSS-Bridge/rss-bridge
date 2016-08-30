<?php
class GuruMedBridge extends BridgeAbstract{

	const MAINTAINER = "qwertygc";
	const NAME = "GuruMed";
	const URI = "http://www.gurumed.org";
	const DESCRIPTION = "Returns the 5 newest posts from Gurumed (full text)";

	private function GurumedStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	public function collectData(){
      $html = $this->getSimpleHTMLDOM(self::URI.'feed')
        or $this->returnServerError('Could not request Gurumed.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = array();
				$item['title'] = $this->GurumedStripCDATA($element->find('title', 0)->innertext);
				$item['uri'] = $this->GurumedStripCDATA($element->find('guid', 0)->plaintext);
				$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
				$item['content'] = $this->GurumedStripCDATA(strip_tags($element->find('description', 0), '<p><a><br>'));
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
