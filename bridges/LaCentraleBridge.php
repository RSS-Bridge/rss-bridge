<?php
class LaCentraleBridge extends BridgeAbstract {

	const MAINTAINER = 'jacknumber';
	const NAME = 'La Centrale';
	const URI = 'https://www.lacentrale.fr/';
	const DESCRIPTION = 'Returns most recent vehicules ads from LaCentrale';

	const PARAMETERS = array( array(
		'type' => array(
			'name' => 'Type de véhicule',
			'type' => 'list',
			'values' => array(
				'Voiture' => 'car',
				'Camion/Pickup' => 'truck',
				'Moto' => 'moto',
				'Caravane/Camping-car' => 'mobileHome'
			)
		),
		'pricemin' => array(
			'name' => 'Prix min',
			'type' => 'number'
		),
		'pricemax' => array(
			'name' => 'Prix max',
			'type' => 'number'
		),
		'location' => array(
			'name' => 'CP ou département',
			'type' => 'number',
			'title' => 'Only one'
		),
		'distance' => array(
			'name' => 'Rayon de recherche',
			'type' => 'list',
			'values' => array(
				'' => '',
				'10 km' => '1',
				'20 km' => '2',
				'50 km' => '3',
				'100 km' => '4',
				'200 km' => '5'
			)
		),
		'mileagemin' => array(
			'name' => 'Kilométrage min',
			'type' => 'number'
		),
		'mileagemax' => array(
			'name' => 'Kilométrage max',
			'type' => 'number'
		),
		'yearmin' => array(
			'name' => 'Année min',
			'type' => 'number'
		),
		'yearmax' => array(
			'name' => 'Année max',
			'type' => 'number'
		),
		'cubiccapacitymin' => array(
			'name' => 'Cylindrée min',
			'type' => 'number'
		),
		'cubiccapacitymax' => array(
			'name' => 'Cylindrée max',
			'type' => 'number'
		),
		'fuel' => array(
			'name' => 'Énergie',
			'type' => 'list',
			'values' => array(
				'' => '',
				'Diesel' => 'dies',
				'Essence' => 'ess',
				'Électrique' => 'elec',
				'Hybride' => 'hyb',
				'GPL' => 'gpl',
				'Bioéthanol' => 'eth',
				'Autre' => 'alt'
			)
		),
		'firsthand' => array(
			'name' => 'Première main',
			'type' => 'checkbox'
		),
		'seller' => array(
			'name' => 'Vendeur',
			'type' => 'list',
			'values' => array(
				'' => '',
				'Particulier' => 'PART',
				'Professionel' => 'PRO'
			)
		),
		'sort' => array(
			'name' => 'Tri',
			'type' => 'list',
			'values' => array(
				'Prix (croissant)' => 'priceAsc',
				'Prix (décroissant)' => 'priceDesc',
				'Marque (croissant)' => 'makeAsc',
				'Marque (décroissant)' => 'makeDesc',
				'Kilométrage (croissant)' => 'mileageAsc',
				'Kilométrage (décroissant)' => 'mileageDesc',
				'Année (croissant)' => 'yearAsc',
				'Année (décroissant)' => 'yearDesc',
				'Département (croissant)' => 'visitPlaceAsc',
				'Département (décroissant)' => 'visitPlaceDesc'
			)
		),
	));

	public function collectData(){
		// check data
		if(!empty($this->getInput('distance'))
		&& is_null($this->getInput('location'))) {
			returnClientError('You need a place ("CP ou département") to search arround.');
		}

		$params = array(
			'vertical' => $this->getInput('type'),
			'priceMin' => $this->getInput('pricemin'),
			'priceMax' => $this->getInput('pricemax'),
			'dptCp' => $this->getInput('location'),
			'distance' => $this->getInput('distance'),
			'mileageMin' => $this->getInput('mileagemin'),
			'mileageMax' => $this->getInput('mileagemax'),
			'yearMin' => $this->getInput('yearmin'),
			'yearMax' => $this->getInput('yearmax'),
			'cubicMin' => $this->getInput('cubiccapacitymin'),
			'cubicMax' => $this->getInput('cubiccapacitymax'),
			'energies' => $this->getInput('fuel'),
			'firstHand' => $this->getInput('firsthand') ? 'true' : 'false',
			'sortBy' => $this->getInput('sort')
		);
		$url = self::URI . 'listing?' . http_build_query($params);
		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request LaCentrale.');

		foreach($html->find('.linkAd') as $element) {

			$item = array();
			$item['uri'] = trim(self::URI, '/') . $element->href;
			$item['title'] = $element->find('.brandModel', 0)->plaintext;
			$item['sellerType'] = $element->find('.typeSeller', 0)->plaintext;
			$item['author'] = $item['sellerType'];
			$item['version'] = $element->find('.version', 0)->plaintext;
			$item['price'] = $element->find('.fieldPrice', 0)->plaintext;
			$item['year'] = $element->find('.fieldYear', 0)->plaintext;
			$item['mileage'] = $element->find('.fieldMileage', 0)->plaintext;
			$item['departement'] = str_replace(',', '', $element->find('.dptCont', 0)->plaintext);
			$item['thumbnail'] = $element->find('.imgContent img', 0)->src;
			$item['enclosures'] = array($item['thumbnail']);

			$item['content'] = ''
			. '<img src="' . $item['thumbnail'] . '">'
			. '<br>Variation : ' . $item['version']
			. '<br>Prix : ' . $item['price']
			. '<br>Année : ' . $item['year']
			. '<br>Kilométrage : ' . $item['mileage']
			. '<br>Département : ' . $item['departement']
			. '<br>Type de vendeur : ' . $item['sellerType']
			;

			$this->items[] = $item;

		}
	}
}
