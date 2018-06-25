<?php
class DealabsBridge extends PepperBridgeAbstract {

	const NAME = 'Dealabs Bridge';
	const URI = 'https://www.dealabs.com/';
	const DESCRIPTION = 'Affiche les Deals de Dealabs';
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
			'group' => array(
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
					'Voyages & sorties' => 'voyages-sorties-restaurants',
				)
			),
			'order' => array(
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

	public $lang = array(
		'bridge-uri' => SELF::URI,
		'bridge-name' => SELF::NAME,
		'context-keyword' => 'Recherche par Mot(s) clé(s)',
		'context-group' => 'Deals par groupe',
		'uri-group' => '/groupe/',
		'request-error' => 'Could not request Dealabs',
		'no-results' => 'Il n&#039;y a rien à afficher pour le moment :(',
		'relative-date-indicator' => array(
			'il y a',
		),
		'price' => 'Prix',
		'shipping' => 'Livraison',
		'origin' => 'Origine',
		'discount' => 'Réduction',
		'title-keyword' => 'Recherche',
		'title-group' => 'Groupe',
		'local-months' => array(
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
		),
		'local-time-relative' => array(
			'il y a ',
			'min',
			'h',
			'jour',
			'jours',
			'mois',
			'ans',
			'et '
		),
		'date-prefixes' => array(
			'Actualisé ',
		),
		'relative-date-alt-prefixes' => array(
			'Actualisé ',
		),
		'relative-date-ignore-suffix' => array(
		),

		'localdeal' => array(
			'Local',
			'Pays d\'expédition'
		),
	);



}

class PepperBridgeAbstract extends BridgeAbstract {

	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		switch($this->queriedContext) {
		case $this->i8n('context-keyword'):
			return $this->collectDataKeywords();
			break;
		case $this->i8n('context-group'):
			return $this->collectDataGroup();
			break;
		}
	}

	/**
	 * Get the Deal data from the choosen group in the choosed order
	 */
	public function collectDataGroup()
	{

		$group = $this->getInput('group');
		$order = $this->getInput('order');

		$url = $this->i8n('bridge-uri')
			. $this->i8n('uri-group') . $group . $order;
		$this->collectDeals($url);
	}

	/**
	 * Get the Deal data from the choosen keywords and parameters
	 */
	public function collectDataKeywords()
	{
		$q = $this->getInput('q');
		$hide_expired = $this->getInput('hide_expired');
		$hide_local = $this->getInput('hide_local');
		$priceFrom = $this->getInput('priceFrom');
		$priceTo = $this->getInput('priceFrom');

		/* Even if the original website uses POST with the search page, GET works too */
		$url = $this->i8n('bridge-uri')
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
			or returnServerError($this->i8n('request-error'));
		$list = $html->find('article[id]');

		// Deal Image Link CSS Selector
		$selectorImageLink = implode(
			' ', /* Notice this is a space! */
			array(
				'cept-thread-image-link',
				'imgFrame',
				'imgFrame--noBorder',
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
				'size--fromW3-m'
			)
		);

		// Deal Date CSS Selector
		$selectorDate = implode(
			' ', /* Notice this is a space! */
			array(
				'size--all-s',
				'flex',
				'flex--justify-e',
				'flex--grow-1',
			)
		);

		// If there is no results, we don't parse the content because it display some random deals
		$noresult = $html->find('h3[class=size--all-l size--fromW2-xl size--fromW3-xxl]', 0);
		if ($noresult != null && strpos($noresult->plaintext, $this->i8n('no-results')) !== false) {
			$this->items = array();
		} else {
			foreach ($list as $deal) {
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
						. $deal->find('div[class*='. $selectorDescription .']', 0)->innertext
						. '</td><td>'
						. $deal->find('div[class='. $selectorHot .']', 0)->children(0)->outertext
						. '</td></table>';
				$dealDateDiv = $deal->find('div[class*='. $selectorDate .']', 0)
					->find('span[class=hide--toW3]');
				$itemDate = end($dealDateDiv)->plaintext;
				// In case of a Local deal, there is no date, but we can use
				//  this case for other reason (like date not in the last field)
				if ($this->contains($itemDate, $this->i8n('localdeal'))) {
					$item['timestamp'] = time();
				}
				// chekf if the relative date indicator is there
				else if ($this->contains($itemDate, $this->i8n('relative-date-indicator'))) {
					$item['timestamp'] = $this->relativeDateToTimestamp($itemDate);
				} else {
					$item['timestamp'] = $this->parseDate($itemDate);
				}
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Check if the string $str contains any of the string of the array $arr
	 * @return boolean true if the string matched anything otherwise false
	 */
	private function contains($str, array $arr)
	{
		foreach ($arr as $a) {
			if (stripos($str, $a) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the Price from a Deal if it exists
	 * @return string String of the deal price
	 */
	private function getPrix($deal)
	{
		if ($deal->find(
			'span[class*=thread-price]', 0) != null) {
			return '<div>'.$this->i8n('price') .' : '
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
		if ($deal->find('span[class*=cept-shipping-price]', 0) != null) {
			if ($deal->find('span[class*=cept-shipping-price]', 0)->children(0) != null) {
				return '<div>'. $this->i8n('shipping') .' : '
					. $deal->find('span[class*=cept-shipping-price]', 0)->children(0)->innertext
					. '</div>';
			} else {
				return '<div>'. $this->i8n('shipping') .' : '
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
		if ($deal->find('a[class=text--color-greyShade]', 0) != null) {
			return '<div>'. $this->i8n('origin') .' : '
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
		if ($deal->find('span[class*=mute--text text--lineThrough]', 0) != null) {
			$discountHtml = $deal->find('span[class=space--ml-1 size--all-l size--fromW3-xl]', 0);
			if ($discountHtml != null) {
				$discount = $discountHtml->plaintext;
			} else {
				$discount = '';
			}
			return '<div>'. $this->i8n('discount') .' : <span style="text-decoration: line-through;">'
				. $deal->find(
					'span[class*=mute--text text--lineThrough]', 0
				)->plaintext
				. '</span>&nbsp;'
				. $discount
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
		if ($deal->find('img[class='. $selectorLazy .']', 0) != null) {
			return json_decode(
				html_entity_decode(
					$deal->find('img[class='. $selectorLazy .']', 0)
					->getAttribute('data-lazy-img')))->{'src'};
		} else {
			return $deal->find('img[class*='. $selectorPlain .']', 0	)->src;
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
		if ($deal->find('span[class='. $selector .']', 0) != null) {
			return '<div>'
				. $deal->find('span[class='. $selector .']', 0)->children(2)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Transforms a local date into a timestamp
	 * @return int timestamp of the input date
	 */
	private function parseDate($string)
	{
		$month_local = $this->i8n('local-months');
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

		// A date can be prfixed with some words, we remove theme
		$string = $this->removeDatePrefixes($string);
		// We translate the local months name in the english one
		$date_str = trim(str_replace($month_local, $month_en, $string));

		// If the date does not contain any year, we add the current year
		if (!preg_match('/[0-9]{4}/', $string)) {
			$date_str .= ' ' . date('Y');
		}

		// Add the Hour and minutes
		$date_str .= ' 00:00';

		$date = DateTime::createFromFormat('j F Y H:i', $date_str);
		return $date->getTimestamp();
	}

	/**
	 * Remove the prefix of a date if it has one
	 * @return the date without prefiux
	 */
	private function removeDatePrefixes($string)
	{
		$string = str_replace($this->i8n('date-prefixes'), array(), $string);
		return $string;
	}

	/**
	 * Remove the suffix of a relative date if it has one
	 * @return the relative date without suffixes
	 */
	private function removeRelativeDateSuffixes($string)
	{
		if (count($this->i8n('relative-date-ignore-suffix')) > 0) {
			$string = preg_replace($this->i8n('relative-date-ignore-suffix'), '', $string);
		}
		return $string;
	}

	/**
	 * Transforms a relative local date into a timestamp
	 * @return int timestamp of the input date
	 */
	private function relativeDateToTimestamp($str) {
		$date = new DateTime();

		// In case of update date, replace it by the regular relative date first word
		$str = str_replace($this->i8n('relative-date-alt-prefixes'), $this->i8n('local-time-relative')[0], $str);

		$str = $this->removeRelativeDateSuffixes($str);

		$search = $this->i8n('local-time-relative');

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

	/**
	 * Returns the RSS Feed title according to the parameters
	 * @return string the RSS feed Tiyle
	 */
	public function getName(){
		switch($this->queriedContext) {
		case $this->i8n('context-keyword'):
			return $this->i8n('bridge-name') . ' - '. $this->i8n('title-keyword') .' : '. $this->getInput('q');
			break;
		case $this->i8n('context-group'):
			$values = static::PARAMETERS[$this->i8n('context-group')]['group']['values'];
			$group = array_search($this->getInput('group'), $values);
			return $this->i8n('bridge-name') . ' - '. $this->i8n('title-group'). ' : '. $group;
			break;
		default: // Return default value
			return static::NAME;
		}
	}



	/**
	 * This is some "localisation" function that returns the needed content using 
	 * the "$lang" class variable in the local class
	 * @return various the local content needed
	 */
	public function i8n($key)
	{
		if (array_key_exists($key, $this->lang)) {
			return $this->lang[$key];
		} else {
			return null;
		}
	}

}

/*

class ExampleDealsBridge extends PepperBridgeAbstract {

	const NAME = 'Example Deals Bridge'; // Bridge Name
	const URI = 'https://www.exampledeal.com/'; // Website Base URL
	const DESCRIPTION = 'Display Deals of Example Deal'; // Bridge Description
	const MAINTAINER = 'My Nickname'; // Bridge Maitainer

	// Parameter name must stays the same because they are used in the Main Bridge
	const PARAMETERS = array(
		'Search by keyword(s))' => array (
			'q' => array(
				'name' => 'Keyword(s)',
				'type' => 'text',
				'required' => true
			),
			'hide_expired' => array(
				'name' => 'Hide expired deals',
				'type' => 'checkbox',
				'required' => 'true'
			),
			'hide_local' => array(
				'name' => 'Hide local deals',
				'type' => 'checkbox',
				'title' => 'Hide deals in physical store',
				'required' => 'true'
			),
			'priceFrom' => array(
				'name' => 'Minimal Price',
				'type' => 'text',
				'title' => 'Minmal Price in Currency',
				'required' => 'false',
				'defaultValue' => ''
			),
			'priceTo' => array(
				'name' => 'Maximum Price',
				'type' => 'text',
				'title' => 'Maximum Price in Currency',
				'required' => 'false',
				'defaultValue' => ''
			),
		),

		'Deals per group' => array(
			'group' => array(
				'name' => 'Group',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Group whose deals must be displayed',
				'values' => array(
					'Group Name' => 'group-url', // group-url is the part of the URL thaht changes between the different groups
				)
			),
			'order' => array(
				'name' => 'Order',
				'type' => 'list',
				'required' => 'true',
				'title' => 'Sort order of deals',
				'values' => array(
					'From the most to the least hot deal' => '-hot', // The value is the part
					// after the group URL that changes between the sort order on the
					// website ; the default sort order type has therefore an empty value
					'From the most recent deal to the oldest' => '',
					'From the most commented deal to the least commented deal' => '-discussed'
				)
			)
		)
	);

	public $lang = array(
		'bridge-uri' => SELF::URI, // Nothing to change
		'bridge-name' => SELF::NAME, // Nothing to change
		'context-keyword' => 'Search by keyword(s))', // This is the same text as in
		//the PARAMETERS constant
		'context-group' => 'Deals per group', // This is the same text as as in the
		// PARAMETERS constant
		'uri-group' => '/tag/', // This is the part of the site URL to display a Group
		'no-results' => 'Ooops, looks like we could', // This is part of the text
		// displayed when a search by keyword does not have any result
		'relative-date-indicator' => 'ago', // This is the text on the website that
		// permits to differentiate between a relative date and a absolute date
		'request-error' => 'Could not request Example Deals', // This is the error
		// message when an access to the Website gone wrong
		// The 4 following lines are part of the text displayed in the content of the RSS Feed ; the name is self explanatory
		'price' => 'Price',
		'shipping' => 'Livraison',
		'origin' => 'Origine',
		'discount' => 'Réduction',
		'title-keyword' => 'Recherche', // This is the text used in the RSS Feed Title for the keyword search mode
		'title-group' => 'Groupe', // This is the text used in the RSS Feed Title for the group mode
		'local-months' => array(
			'Jan',
			'Feb',
			'Mar',
			'Apr',
			'May',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Occ',
			'Nov',
			'Dec',
			'st',
			'nd',
			'rd',
			'th'

		), // This is the list of months used in the date of the website : they could
		// be short or long names, depending of the website ; if the website add some
		// text after the day number, you must add them after the last month
		'local-time-relative' => array(
			'Found ',
			'm',
			'h,',
			'day',
			'days',
			'month',
			'year',
			'and '
		), // This is the list of local "words" that appears in a relative date
		'date-prefixes' => array(
			'Found ',
			'Refreshed ',
			'Made hot '
		), // This is a list of "Words" that could have been added before an absolute date ; this is very dependant of website
		'relative-date-alt-prefixes' => array(
			'Actualisé ',
		), // This is a list of "Words" that could replace the default first word of
		// a relative date (the first word of the list named 'local-time-relative'
		'relative-date-ignore-suffix' => array(
			'/by.*$/'
		), // Array of regular expression that contains pattern thaht match the
		// suffix of relative date that could appear (like in the "hot" sort order)
		'localdeal' => array(
			'Local',
			'Expires'
		), // For some Deals, like the Local deals, there is no date ; We must catch
		// those case, and we must list the "Words" that is displayed on those deals
		// on the top and the most right section of the deal
	)
	;



}

 */
