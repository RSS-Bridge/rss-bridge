<?php
class DealabsBridge extends BridgeAbstract {
	const NAME = 'Dealabs search bridge';
	const URI = 'https://www.dealabs.com/';
	const DESCRIPTION = 'Return the Dealabs search result using keywords,
 with/without expired deals, with/without shop deals and by category';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array( array (
		'q' => array(
			'name' => 'Mot(s) clé(s)',
			'type' => 'text',
			'required' => true
		),
		'expired_choice' => array(
			'name' => 'Afficher deals expirés',
			'type' => 'checkbox'
		),
		'instore_choice' => array(
			'name' => 'Afficher deals en magasin',
			'type' => 'checkbox'
		),
		'cat' => array(
			'name' => 'Catégorie',
			'type' => 'list',
			'values' => array(
				'Toutes les catégories' => '',
				'High-tech' => array(
					'Tous' => 'c2',
					'Informatique' => 's3',
					'Téléphonie' => 's4',
					'Accessoires, consommables' => 's6',
					'Gadgets' => 's8',
					'Applications, logiciels' => 's46'
				),
				'Audiovisuel' => array(
					'Tous' => 'c5',
					'Image et son' => 's9',
					'Photo, caméscopes' => 's10',
					'CD, DVD, Blu-ray' => 's11',
					'Jeux vidéo, consoles' => 's12'
				),
				'Loisirs' => array(
					'Tous' => 'c7',
					'Jeux, jouets' => 's13',
					'Livres, papeterie' => 's14',
					'Plein air' => 's15',
					'Sport' => 's35',
					'Auto/Moto, accessoires' => 's37',
					'Animaux, accessoires' => 's47',
					'Instruments de musique' => 's48'
				),
				'Mode' => array(
					'Tous' => 'c16',
					'Homme' => 's17',
					'Femme' => 's18',
					'Mixte' => 's50',
					'Enfants' => 's19',
					'Puériculture' => 's36',
					'Beauté, santé' => 's21',
					'Bijoux, accessoires' => 's20',
					'Bagagerie' => 's38'
				),
				'Maison' => array(
					'Tous' => 'c23',
					'Meuble, literie, déco' => 's24',
					'Cuisine, art de la table' => 's25',
					'Électroménager' => 's26',
					'Bricolage' => 's27',
					'Jardin' => 's28'
				),
				'Services' => array(
					'Tous' => 'c51',
					'Voyages' => 's57',
					'Hébergement, restauration' => 's52',
					'Sorties' => 's53',
					'Presse' => 's24',
					'Bien-être' => 's55',
					'Transport, expédition' => 's56',
					'Autres' => 's58'
				),
				'Épicerie' => 'c31'

			)
		)


	));

	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		$q = $this->getInput('q');

		$expired_choice = $this->getInput('expired_choice');
		$instore_choice = $this->getInput('instore_choice');
		$cat_subcat = $this->getInput('cat');
		$html = getSimpleHTMLDOM(self::URI
			. '/search/?q='
			. urlencode($q)
			. '&hide_expired='
			. $expired_choice
			. '&hide_instore='
			. $instore_choice
			. '&' . $this->getCatSubcatParam($cat_subcat))
			or returnServerError('Could not request Dealabs.');
		$list = $html->find('article');
		if($list === null) {
			returnClientError('Your combination of parameters returned no results');
		}

		foreach($list as $deal) {
			$item = array();
			$item['uri'] = $deal->find('a.title', 0)->href;
			$item['title'] = $deal->find('a.title', 0)->plaintext;
			$item['author'] = $deal->find('a.poster_link', 0)->plaintext;
			$item['content'] = '<table><tr><td>'
				. $deal->find('div.image_part', 0)->outertext
				. '</td><td>'
				. $deal->find('a.title', 0)->outertext
				. $deal->find('p.description', 0)->outertext
				. '</td><td>'
				. $deal->find('div.vote_part', 0)->outertext
				. '</td></table>';
			$item['timestamp'] = $this->relativeDateToTimestamp(
				$deal->find('p.date_deal', 0)->plaintext);
			$this->items[] = $item;
		}

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
			'ans'
		);
		$replace = array(
			'-',
			'minute',
			'hour',
			'day',
			'month',
			'year'
		);

		$date->modify(str_replace($search, $replace, $str));
		return $date->getTimestamp();
	}

	private function getCatSubcatParam($str) {
		if(strlen($str) >= 2) {
			if(substr($str, 0, 1) == 'c') {
				$var_name = 'cat[]';
			} else if(substr($str, 0, 1) == 's') {
				$var_name = 'sub_cat[]';
			}
			$value = substr($str, 1);
			return $var_name .'='. $value;
		} else {
			return '';
		}
	}

}
