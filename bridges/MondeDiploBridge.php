<?php
class MondeDiploBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "Pitchoule";
		$this->name = 'Monde Diplomatique';
		$this->uri = 'http://www.monde-diplomatique.fr';
		$this->description = "Returns most recent results from MondeDiplo.";
		$this->update = "2016-08-09";
	}

	public function collectData(array $param){	
		$html = $this->file_get_html($this->uri) or $this->returnError('Could not request MondeDiplo. for : ' . $link , 404);

		foreach($html->find('div.unarticle') as $article) {
			$element = $article->parent();
			$item = new Item();
			$item->uri = $this->uri . $element->href;
			$item->title = $element->find('h3', 0)->plaintext;
			$item->content = $element->find('div.dates_auteurs', 0)->plaintext . '<br>' . strstr($element->find('div', 0)->plaintext, $element->find('div.dates_auteurs', 0)->plaintext, true);
			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 21600; // 6 hours
	}
}
