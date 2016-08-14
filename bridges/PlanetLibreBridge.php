<?php
class PlanetLibreBridge extends BridgeAbstract{

	public function loadMetadatas(){
		$this->maintainer = "pit-fgfjiudghdf";
		$this->name = "PlanetLibre";
		$this->uri = "http://www.planet-libre.org";
		$this->description = "Returns the 5 newest posts from PlanetLibre (full text)";
		$this->update = "2016-08-09";
	}

	private function PlanetLibreExtractContent($url){
		$html2 = $this->file_get_html($url);
		$text = $html2->find('div[class="post-text"]', 0)->innertext;
		return $text;
	}

	public function collectData(array $param){
		$html = $this->file_get_html('http://www.planet-libre.org/') or $this->returnError('Could not request PlanetLibre.', 404);
		$limit = 0;
		foreach($html->find('div.post') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = $element->find('h1', 0)->plaintext;
				$item->uri = $element->find('a', 0)->href;
				$item->timestamp = strtotime(str_replace('/', '-', $element->find('div[class="post-date"]', 0)->plaintext));
				$item->content = $this->PlanetLibreExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 3600*2; // 1 hour
	}
}
