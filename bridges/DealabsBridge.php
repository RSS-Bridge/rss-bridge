<?php
class DealabsBridge extends BridgeAbstract {
	const NAME = 'Dealabs search bridge';
	const URI = 'https://www.dealabs.com/';
	const DESCRIPTION = 'Return the Dealabs search result using keywords';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array( array (
		'q' => array(
			'name' => 'Mot(s) clé(s)',
			'type' => 'text',
			'required' => true
		),
	));

	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		$q = $this->getInput('q');

		$html = getSimpleHTMLDOM(self::URI
			. '/search/?q='
			. urlencode($q))
			or returnServerError('Could not request Dealabs.');
		$list = $html->find('article');
		if($list === null) {
			returnClientError('Your combination of parameters returned no results');
		}

		foreach($list as $deal) {
			$item = array();
			$item['uri'] = $deal->find('div[class=fGrid-right space--l-2]', 0)->find('a', 0)->href;
			$item['title'] = $deal->find(
				'a[class=cept-tt thread-link linkPlain space--r-1 size--all-s size--fromW2-m]', 0
				)->plaintext;
			$item['author'] = $deal->find('span.thread-username', 0)->plaintext;
			$item['content'] = '<table><tr><td><a href="'
				. $deal->find(
					'a[class*=cept-thread-image-link imgFrame imgFrame--noBorder box--all-i thread-listImgCell]', 0)->href
				. '"><img src="'
				. $this->getImage($deal)
				. '"/></td><td><h2><a href="'
				. $deal->find('a[class=cept-tt thread-link linkPlain space--r-1 size--all-s size--fromW2-m]', 0)->href
				. '">'
				. $deal->find('a[class=cept-tt thread-link linkPlain space--r-1 size--all-s size--fromW2-m]', 0)->innertext
				. '</a></h2>'
				. $this->getPrix($deal)
				. $this->getReduction($deal)
				. $this->getExpedition($deal)
				. $this->getLivraison($deal)
				. $this->getOrigine($deal)
				. $deal->find(
					'div[class=cept-description-container overflow--wrap-break size--all-s size--fromW2-m space--fromW3-b-2]', 0
					)->innertext
				. '</td><td>'
				. $deal->find('div[class=flex flex--align-c flex--justify-space-between space--b-2]', 0)->children(0)->outertext
				. '</td></table>';
			$dealPossibleDates = $deal->find('span[class=meta-ribbon hide--toW3 space--l-3 text--color-greyShade]');
			$itemDate = end($dealPossibleDates)->children(1)->plaintext;
			if(substr( $itemDate, 0, 6 ) === 'il y a') {
				$item['timestamp'] = $this->relativeDateToTimestamp($itemDate);
			} else 	{
				$item['timestamp'] = $this->parseDate($itemDate);
			}
			$this->items[] = $item;
		}

	}

	private function getPrix($deal)
	{
		if($deal->find(
			'span[class*=thread-price text--b vAlign--all-tt cept-tp size--all-m size--fromW2-xxl size--fromW4-xxxl]', 0) != null) {
			return '<div>Prix : '
				. $deal->find(
					'span[class*=thread-price text--b vAlign--all-tt cept-tp size--all-m size--fromW2-xxl size--fromW4-xxxl]', 0
				)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}


	private function getLivraison($deal)
	{
		if($deal->find('span[class=size--all-s overflow--wrap-off cept-shipping-price]', 0) != null) {
			if($deal->find('span[class=size--all-s overflow--wrap-off cept-shipping-price]', 0)->children(0) != null)
			{
				return '<div>Livraison : '
				. $deal->find('span[class=size--all-s overflow--wrap-off cept-shipping-price]', 0)->children(0)->innertext
				. '</div>';
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

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

	private function getReduction($deal)
	{
		if($deal->find('span[class=mute--text size--all-s space--l-2 text--lineThrough]', 0) != null) {
			return '<div>Réduction : <span style="text-decoration: line-through;">'
				. $deal->find(
					'span[class=mute--text size--all-s space--l-2 text--lineThrough]', 0
					)->plaintext
				. '</span>&nbsp;'
				. $deal->find('span[class=mute--text size--all-s]', 0)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

	private function getImage($deal)
	{
		if($deal->find(
			'img[class=thread-image width--all-auto height--all-auto imgFrame-img cept-thread-img img--dummy js-lazy-img]', 0) != null) {
			return json_decode(
				html_entity_decode(
					$deal->find(
						'img[class=thread-image width--all-auto height--all-auto imgFrame-img cept-thread-img img--dummy js-lazy-img]', 0)
						->getAttribute('data-lazy-img')))->{'src'};
		} else {

			return $deal->find(
				'img[class=thread-image width--all-auto height--all-auto imgFrame-img cept-thread-img]', 0
				)->src;
		}
	}

	private function getExpedition($deal)
	{
		if($deal->find('span[class=meta-ribbon hide--toW3 space--l-3 text--color-greyShade]', 0) != null) {
			return '<div>'
				. $deal->find('span[class=meta-ribbon hide--toW3 space--l-3 text--color-greyShade]', 0)->children(1)->plaintext
				. '</div>';
		} else {
			return '';
		}
	}

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
			$date_str.=' ' . date('Y');
		}
		$date_str.=' 00:00';

		$date = DateTime::createFromFormat('j F Y H:i', $date_str);
		return $date->getTimestamp();
	}

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

}
