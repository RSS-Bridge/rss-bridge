<?php
/**
* Retourne les dons d'une recherche filtrée sur le site Donnons.org
* Example: https://donnons.org/Sport/Ile-de-France
*/
class DonnonsBridge extends BridgeAbstract {

	const MAINTAINER = 'Binnette';
	const NAME = 'Donnons.org';
	const URI = 'https://donnons.org/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Retourne les dons depuis le site Donnons.org.';

	const PARAMETERS = array(array(
		'q' => array(
			'name' => 'keyword',
			'required' => true,
			'exampleValue' 	=> '/Sport/Ile-de-France',
			'title' => 'Depuis le site, choisir des filtres par exemple Sport et Ile de France et lancez la recherche. Puis copiez ici la fin de l\'url',
		)
	));

	public function collectData(){
		$html = '';

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('No results for this query.');

		$results = $html->find('div[id=search]', 0);

		if(!is_null($results)) {
			foreach($results->find('a[class=lst-annonce]') as $element) {
				$item = array();

				// Lien vers le don
				$item['uri'] = $element->href;

				// Grab info
				$json = $element->find('script')->plaintext;
				$objectName = $element->find('h2[id=title]')->plaintext;
				$objectCity = $element->find('span[class=city]')->plaintext;


				// Titre du don
				$item['title'] = $objectName;
				//$item['timestamp']  // Timestamp of the item in numeric or text format (compatible for strtotime())
				//$item['author']     // Name of the author for this item
				//$item['content']    // Content in HTML format
				$item['content'] = $objectName . ' - ' . $objectCity;
				//$item['enclosures'] // Array of URIs to an attachments (pictures, files, etc...)
				//$item['categories'] // Array of categories / tags / topics
				//$item['uid']        // A unique ID to identify the current item

				$this->items[] = $item;
			}
		}
	}

	public function getURI() {
		if (!is_null($this->getInput('q'))) {
			return self::URI
				. $this->getInput('q');
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('q'))) {
			return 'Donnons.org - ' . $this->getInput('q');
		}

		return parent::getName();
	}
}
