<?php
class MondeDiploBridge extends BridgeAbstract{

	const MAINTAINER = "Pitchoule";
	const NAME = 'Monde Diplomatique';
	const URI = 'http://www.monde-diplomatique.fr/';
	const CACHE_TIMEOUT = 21600; //6h
	const DESCRIPTION = "Returns most recent results from MondeDiplo.";

	public function collectData(){
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request MondeDiplo. for : ' . self::URI);

		foreach($html->find('div.unarticle') as $article) {
			$element = $article->parent();
			$item = array();
			$item['uri'] = self::URI . $element->href;
			$item['title'] = $element->find('h3', 0)->plaintext;
			$item['content'] = $element->find('div.dates_auteurs', 0)->plaintext . '<br>' . strstr($element->find('div', 0)->plaintext, $element->find('div.dates_auteurs', 0)->plaintext, true);
			$this->items[] = $item;
		}
	}
}
