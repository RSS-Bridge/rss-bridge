<?php
class SensCritiqueBridge extends BridgeAbstract {

	const MAINTAINER = 'kranack';
	const NAME = 'Sens Critique';
	const URI = 'https://www.senscritique.com/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Sens Critique news';

	const PARAMETERS = array( array(
		's' => array(
			'name' => 'Series',
			'type' => 'checkbox'
		),
		'g' => array(
			'name' => 'Video Games',
			'type' => 'checkbox'
		),
		'b' => array(
			'name' => 'Books',
			'type' => 'checkbox'
		),
		'bd' => array(
			'name' => 'BD',
			'type' => 'checkbox'
		),
		'mu' => array(
			'name' => 'Music',
			'type' => 'checkbox'
		)
	));

	public function collectData(){
		$categories = array();
		foreach(self::PARAMETERS[$this->queriedContext] as $category => $properties) {
			if($this->getInput($category)) {
				$uri = self::URI;
				switch($category) {
				case 's': $uri .= 'series/actualite';
				break;
				case 'g': $uri .= 'jeuxvideo/actualite';
				break;
				case 'b': $uri .= 'livres/actualite';
				break;
				case 'bd': $uri .= 'bd/actualite';
				break;
				case 'mu': $uri .= 'musique/actualite';
				break;
				}
				$html = getSimpleHTMLDOM($uri)
					or returnServerError('No results for this query.');
				$list = $html->find('ul.elpr-list', 0);

				$this->extractDataFromList($list);
			}
		}
	}

	private function extractDataFromList($list){
		if($list === null) {
			returnClientError('Cannot extract data from list');
		}

		foreach($list->find('li') as $movie) {
			$item = array();
			$item['author'] = htmlspecialchars_decode($movie->find('.elco-title a', 0)->plaintext, ENT_QUOTES)
			. ' '
			. $movie->find('.elco-date', 0)->plaintext;

			$item['title'] = $movie->find('.elco-title a', 0)->plaintext
			. ' '
			. $movie->find('.elco-date', 0)->plaintext;

			$item['content'] = '';
			$originalTitle = $movie->find('.elco-original-title', 0);
			$description = $movie->find('.elco-description', 0);

			if ($originalTitle) {
				$item['content'] = '<em>' . $originalTitle->plaintext . '</em><br><br>';
			}

			$item['content'] .= $movie->find('.elco-baseline', 0)->plaintext
			. '<br>'
			. $movie->find('.elco-baseline', 1)->plaintext
			. '<br><br>'
			. ($description ? $description->plaintext : '')
			. '<br><br>'
			. trim($movie->find('.erra-ratings .erra-global', 0)->plaintext)
			. ' / 10';

			$item['id'] = $this->getURI() . ltrim($movie->find('.elco-title a', 0)->href, '/');
			$item['uri'] = $this->getURI() . ltrim($movie->find('.elco-title a', 0)->href, '/');
			$this->items[] = $item;
		}
	}
}
