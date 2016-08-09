<?php
class GuruMedBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "qwertygc";
		$this->name = "GuruMed";
		$this->uri = "http://www.gurumed.org";
		$this->description = "Returns the 5 newest posts from Gurumed (full text)";
		$this->update = "2016-08-09";
	}

	private function GurumedStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	public function collectData(array $param){
		$html = $this->file_get_html('http://gurumed.org/feed') or $this->returnError('Could not request Gurumed.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = $this->GurumedStripCDATA($element->find('title', 0)->innertext);
				$item->uri = $this->GurumedStripCDATA($element->find('guid', 0)->plaintext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->GurumedStripCDATA(strip_tags($element->find('description', 0), '<p><a><br>'));
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
