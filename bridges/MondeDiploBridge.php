<?php
class MondeDiploBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "Pitchoule";
		$this->name = 'Monde Diplomatique';
		$this->uri = 'http://www.monde-diplomatique.fr';
		$this->description = "Returns most recent results from MondeDiplo.";
	}

	public function collectData(){
		$html = $this->getSimpleHTMLDOM($this->uri) or $this->returnServerError('Could not request MondeDiplo. for : ' . $link);

		foreach($html->find('div.unarticle') as $article) {
			$element = $article->parent();
			$item = array();
			$item['uri'] = $this->uri . $element->href;
			$item['title'] = $element->find('h3', 0)->plaintext;
			$item['content'] = $element->find('div.dates_auteurs', 0)->plaintext . '<br>' . strstr($element->find('div', 0)->plaintext, $element->find('div.dates_auteurs', 0)->plaintext, true);
			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 21600; // 6 hours
	}
}
