<?php
class GuruMedBridge extends BridgeAbstract{

	public $maintainer = "qwertygc";
	public $name = "GuruMed";
	public $uri = "http://www.gurumed.org";
	public $description = "Returns the 5 newest posts from Gurumed (full text)";

	private function GurumedStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	public function collectData(){
      $html = $this->getSimpleHTMLDOM($this->uri.'feed')
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
