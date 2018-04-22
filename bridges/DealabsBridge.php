<?php
class DealabsBridge extends BridgeAbstract {
	const NAME = 'Dealabs search bridge';
	const URI = 'https://www.dealabs.com/';
	const DESCRIPTION = 'Return the Dealabs search result using keywords';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Recherche par Mot(s) clé(s)' => array (
			'q' => array(
				'name' => 'Mot(s) clé(s)',
				'type' => 'text',
				'required' => true
			),
			'hide_expired' => array(
				'name' => 'Masquer les éléments expirés',
				'type' => 'checkbox',
				'required' => 'true'
			),
			'hide_local' => array(
				'name' => 'Masquer les deals locaux',
				'type' => 'checkbox',
				'title' => 'Masquer les deals en magasins physiques',
				'required' => 'true'
			),
			'priceFrom' => array(
				'name' => 'Prix minimum',
				'type' => 'text',
				'title' => 'Prix mnimum en euros',
				'required' => 'false',
				'defaultValue' => ''
			),
			'priceTo' => array(
				'name' => 'Prix maximum',
				'type' => 'text',
				'title' => 'Prix maximum en euros',
				'required' => 'false',
				'defaultValue' => ''
			),
		),

		'Deals par groupe' => array(
			'groupe' => array(
				'name' => 'Groupe',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Groupe dont il faut afficher les deals',
				'values' => array(
					'Accessoires & gadgets' => 'accessoires-gadgets',
					'Alimentation & boissons' => 'alimentation-boissons',
					'Animaux' => 'animaux',
					'Applis & logiciels' => 'applis-logiciels',
					'Consoles & jeux vidéo' => 'consoles-jeux-video',
					'Culture & divertissement' => 'culture-divertissement',
					'Gratuit' => 'gratuit',
					'Image, son & vidéo' => 'image-son-video',
					'Informatique' => 'informatique',
					'Jeux & jouets' => 'jeux-jouets',
					'Maison & jardin' => 'maison-jardin',
					'Mode & accessoires' => 'mode-accessoires',
					'Santé & cosmétiques' => 'hygiene-sante-cosmetiques',
					'Services divers' => 'services-divers',
					'Sports & plein air' => 'sports-plein-air',
					'Téléphonie' => 'telephonie',
					'Voyages & sorties' => 'voyages-sorties-restaurants'
				)
			),
			'ordre' => array(
				'name' => 'Trier par',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Ordre de tri des deals',
				'values' => array(
					'Du deal le plus Hot au moins Hot' => '',
					'Du deal le plus récent au plus ancien' => '-nouveaux',
					'Du deal le plus commentés au moins commentés' => '-commentes'
				)
			)
		)
	);

	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		switch($this->queriedContext) {
		case 'Recherche par Mot(s) clé(s)':
			return $this->collectDataMotsCles();
			break;
		case 'Deals par groupe':
			return $this->collectDataGroupe();
			break;
		}
	}

	/**
	 * Get the Deal data from the choosen groupe in the choose order
	 */
	public function collectDataGroupe()
	{

		$groupe = $this->getInput('groupe');
		$ordre = $this->getInput('ordre');

		$url = self::URI
			. '/groupe/' . $groupe . $ordre;
		$this->collectDeals($url);
	}

	/**
	 * Get the Deal data from the choosen keywords and parameters
	 */
	public function collectDataMotsCles()
	{
		$q = $this->getInput('q');
		$hide_expired = $this->getInput('hide_expired');
		$hide_local = $this->getInput('hide_local');
		$priceFrom = $this->getInput('priceFrom');
		$priceTo = $this->getInput('priceFrom');

		/* Even if the original website uses POST with the search page, GET works too */
		$url = self::URI
			. '/search/advanced?q='
			. urlencode($q)
			. '&hide_expired='. $hide_expired
			. '&hide_local='. $hide_local
			. '&priceFrom='. $priceFrom
			. '&priceTo='. $priceTo
			/* Some default parameters
			 * search_fields : Search in Titres & Descriptions & Codes
			 * sort_by : Sort the search by new deals
			 * time_frame : Search will not be on a limited timeframe
			 */
			. '&search_fields[]=1&search_fields[]=2&search_fields[]=3&sort_by=new&time_frame=0';
		$this->collectDeals($url);
	}

	/**
	 * Get the Deal data using the given URL
	 */
	public function collectDeals($url){
		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request Dealabs.');
		$list = $html->find('article');

		// Deal Image Link CSS Selector
		$selectorImageLink = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-thread-image-link',
				'imgFrame',
				'imgFrame--noBorder',
				'box--all-i',
				'thread-listImgCell',
			)
		);

		// Deal Link CSS Selector
		$selectorLink = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-tt',
				'thread-link',
				'linkPlain',
			)
		);

		// Deal Hotness CSS Selector
		$selectorHot = implode(
			' ', /* Notice this is a space! */
			array(
				'flex',
				'flex--align-c',
				'flex--justify-space-between',
				'space--b-2',
			)
		);

		// Deal Description CSS Selector
		$selectorDescription = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-description-container',
				'overflow--wrap-break',
				'size--all-s',
				'size--fromW3-m',
			)
		);

		// Deal Date CSS Selector
		$selectorDate = implode(
			' ', /* Notice this is a space! */
			array(
				'size--all-s',
				'flex',
				'flex--wrap',
				'flex--justify-e',
				'flex--grow-1',
			)
		);

		// If there is no results, we don't parse the content because it display some random deals
		$noresult = $html->find('h3[class=size--all-l size--fromW2-xl size--fromW3-xxl]', 0);
		if($noresult != null && $noresult->plaintext == 'Il n&#039;y a rien à afficher pour le moment :(') {
			$this->items = array();
		} else {
			foreach($list as $deal) {
				$item = array();
				$item['uri'] = $deal->find('div[class=threadGrid-title]', 0)->find('a', 0)->href;
				$item['title'] = $deal->find('a[class*='. $selectorLink .']', 0
					)->plaintext;
				$item['author'] = $deal->find('span.thread-username', 0)->plaintext;
				$item['content'] = '<table><tr><td><a href="'
					. $deal->find(
						'a[class*='. $selectorImageLink .']', 0)->href
					. '"><img src="'
					. $this->getImage($deal)
					. '"/></td><td><h2><a href="'
					. $deal->find('a[class*='. $selectorLink .']', 0)->href
					. '">'
					. $deal->find('a[class*='. $selectorLink .']', 0)->innertext
					. '</a></h2>'
					. $this->getPrix($deal)
					. $this->getReduction($deal)
					. $this->getExpedition($deal)
					. $this->getLivraison($deal)
					. $this->getOrigine($deal)
					. $deal->find('div[class='. $selectorDescription .']', 0)->innertext
					. '</td><td>'
					. $deal->find('div[class='. $selectorHot .']', 0)->children(0)->outertext
					. '</td></table>';
				$dealDateDiv = $deal->find('div[class='. $selectorDate .']', 0)
					->find('span[class=hide--toW3]');
				$itemDate = end($dealDateDiv)->plaintext;
				if(substr( $itemDate, 0, 6 ) === 'il y a') {
					$item['timestamp'] = $this->relativeDateToTimestamp($itemDate);
				} else 	{
					$item['timestamp'] = $this->parseDate($itemDate);
				}
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Get the Price from a Deal if it exists
	 * @return string String of the deal price
	 */
	private function getPrix($deal)
	{
		if($deal->find(
			'span[class*=thread-price]', 0) != null) {
			return '<div>Prix : '
				. $deal->find(
					'span[class*=thread-price]', 0
				)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}


	/**
	 * Get the Shipping costs from a Deal if it exists
	 * @return string String of the deal shipping Cost
	 */
	private function getLivraison($deal)
	{
		if($deal->find('span[class*=cept-shipping-price]', 0) != null) {
			if($deal->find('span[class*=cept-shipping-price]', 0)->children(0) != null) {
				return '<div>Livraison : '
				. $deal->find('span[class*=cept-shipping-price]', 0)->children(0)->innertext
				. '</div>';
			} else {
				return '<div>Livraison : '
				. $deal->find('span[class*=cept-shipping-price]', 0)->innertext
				. '</div>';
			}
		} else {
			return '';
		}
	}

	/**
	 * Get the source of a Deal if it exists
	 * @return string String of the deal source
	 */
	private function getOrigine($deal)
	{
		if($deal->find('a[class=text--color-greyShade]', 0) != null) {
			return '<div>Origine : '
				. $deal->find('a[class=text--color-greyShade]', 0)->outertext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Get the original Price and discout from a Deal if it exists
	 * @return string String of the deal original price and discount
	 */
	private function getReduction($deal)
	{
		if($deal->find('span[class*=mute--text text--lineThrough]', 0) != null) {
			return '<div>Réduction : <span style="text-decoration: line-through;">'
				. $deal->find(
					'span[class*=mute--text text--lineThrough]', 0
					)->plaintext
				. '</span>&nbsp;'
				. $deal->find('span[class=space--ml-1 size--all-l size--fromW3-xl]', 0)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Get the Picture URL from a Deal if it exists
	 * @return string String of the deal Picture URL
	 */
	private function getImage($deal)
	{

		$selectorLazy = implode(
			' ', /* Notice this is a space! */
			array(
				'thread-image',
				'width--all-auto',
				'height--all-auto',
				'imgFrame-img',
				'cept-thread-img',
				'img--dummy',
				'js-lazy-img'
			)
		);

			$selectorPlain = implode(
			' ', /* Notice this is a space! */
			array(
				'thread-image',
				'width--all-auto',
				'height--all-auto',
				'imgFrame-img',
				'cept-thread-img'
			)
		);
		if($deal->find('img[class='. $selectorLazy .']', 0) != null) {
			return json_decode(
				html_entity_decode(
					$deal->find('img[class='. $selectorLazy .']', 0)
						->getAttribute('data-lazy-img')))->{'src'};
		} else {
			return $deal->find('img[class='. $selectorPlain .']', 0	)->src;
		}
	}

	/**
	 * Get the originating country from a Deal if it existsa
	 * @return string String of the deal originating country
	 */
	private function getExpedition($deal)
	{
		$selector = implode(
			' ', /* Notice this is a space! */
			array(
				'meta-ribbon',
				'overflow--wrap-off',
				'space--l-3',
				'text--color-greyShade'
			)
		);
		if($deal->find('span[class='. $selector .']', 0) != null) {
			return '<div>'
				. $deal->find('span[class='. $selector .']', 0)->children(2)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Transforms a French date into a timestam
	 * @return int timestamp of the input date
	 */
	private function parseDate($string)
	{
		$month_fr = array(
			'janvier',
			'février',
			'mars',
			'avril',
			'mai',
			'juin',
			'juillet',
			'août',
			'septembre',
			'octobre',
			'novembre',
			'décembre'
		);
		$month_en = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'
		);
		$date_str = trim(str_replace($month_fr, $month_en, $string));

		if(!preg_match('/[0-9]{4}/', $string)) {
			$date_str .= ' ' . date('Y');
		}
		$date_str .= ' 00:00';

		$date = DateTime::createFromFormat('j F Y H:i', $date_str);
		return $date->getTimestamp();
	}

	/**
	 * Transforms a relate French date into a timestam
	 * @return int timestamp of the input date
	 */
	private function relativeDateToTimestamp($str) {
		$date = new DateTime();
		$search = array(
			'il y a ',
			'min',
			'h',
			'jour',
			'jours',
			'mois',
			'ans',
			'et '
		);
		$replace = array(
			'-',
			'minute',
			'hour',
			'day',
			'month',
			'year',
			''
		);

		$date->modify(str_replace($search, $replace, $str));
		return $date->getTimestamp();
	}

	public function getName(){
		switch($this->queriedContext) {
			case 'Recherche par Mot(s) clé(s)':
				return self::NAME . ' - Recherche : '. $this->getInput('q');
				break;
			case 'Deals par groupe':
				$values = self::PARAMETERS['Deals par groupe']['groupe']['values'];
				$groupe = array_search($this->getInput('groupe'), $values);
				return self::NAME . ' - Groupe : '. $groupe;
				break;
			default: // Return default value
				return self::NAME;
		}
	}

}
