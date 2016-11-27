<?php
class PlanetLibreBridge extends BridgeAbstract{

	const MAINTAINER = "pit-fgfjiudghdf";
	const NAME = "PlanetLibre";
	const URI = "http://www.planet-libre.org";
	const DESCRIPTION = "Returns the 5 newest posts from PlanetLibre (full text)";

	private function PlanetLibreExtractContent($url){
		$html2 = getSimpleHTMLDOM($url);
		$text = $html2->find('div[class="post-text"]', 0)->innertext;
		return $text;
	}

	public function collectData(){
      $html = getSimpleHTMLDOM(self::URI)
        or returnServerError('Could not request PlanetLibre.');
		$limit = 0;
		foreach($html->find('div.post') as $element) {
			if($limit < 5) {
				$item = array();
				$item['title'] = $element->find('h1', 0)->plaintext;
				$item['uri'] = $element->find('a', 0)->href;
				$item['timestamp'] = strtotime(str_replace('/', '-', $element->find('div[class="post-date"]', 0)->plaintext));
				$item['content'] = $this->PlanetLibreExtractContent($item['uri']);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
