<?php

class AutoJMBridge extends BridgeAbstract {

	const NAME = 'AutoJM';
	const URI = 'https://www.autojm.fr/';
	const DESCRIPTION = 'Suivre les offres de véhicules proposés par AutoJM en fonction des critères de filtrages';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Afficher les offres de véhicules disponible en fonction des critères du site AutoJM' => array(
			'url' => array(
				'name' => 'URL du modèle',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une recherche avec filtre de véhicules sans le http://www.autojm.fr/',
				'exampleValue' => 'achat-voitures-neuves-peugeot-nouvelle-308-5p'
			),
			'energy' => array(
				'name' => 'Carburant',
				'type' => 'list',
				'values' => array(
					'-' => '',
					'Diesel' => 1,
					'Essence' => 3,
					'Hybride' => 5
				),
				'title' => 'Carburant'
			),
			'transmission' => array(
				'name' => 'Transmission',
				'type' => 'list',
				'values' => array(
					'-' => '',
					'Automatique' => 1,
					'Manuelle' => 2
				),
				'title' => 'Transmission'
			),
			'priceMin' => array(
				'name' => 'Prix minimum',
				'type' => 'number',
				'required' => false,
				'title' => 'Prix minimum du véhicule',
				'exampleValue' => '10000',
				'defaultValue' => '0'
			),
			'priceMax' => array(
				'name' => 'Prix maximum',
				'type' => 'number',
				'required' => false,
				'title' => 'Prix maximum du véhicule',
				'exampleValue' => '15000',
				'defaultValue' => '150000'
			)
		)
	);
	const CACHE_TIMEOUT = 3600;

	public function getIcon() {
		return self::URI . 'favicon.ico';
	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Afficher les offres de véhicules disponible en fonction des critères du site AutoJM':
			$html = getSimpleHTMLDOMCached(self::URI . $this->getInput('url'), 86400);
			$name = html_entity_decode($html->find('title', 0)->plaintext);
			return $name;
			break;
		default:
			return parent::getName();
		}

	}

	public function collectData() {

		$model_url = self::URI . $this->getInput('url');

		// Build the GET data
		$get_data = 'form[energy]=' . $this->getInput('energy') .
			'&form[transmission]=' . $this->getInput('transmission') .
			'&form[priceMin]=' . $this->getInput('priceMin') .
			'&form[priceMin]=' . $this->getInput('priceMin');

		// Set the header 'X-Requested-With' like the website does it
		$header = array(
			'X-Requested-With: XMLHttpRequest'
		);

		// Get the JSON content of the form
		$json = getContents($model_url . '?' . $get_data, $header)
			or returnServerError('Could not request AutoJM.');

		// Extract the HTML content from the JSON result
		$data = json_decode($json);
		$html = str_get_html($data->results);

		// Go through every car of the model
		$list = $html->find('div[class=car-card]');
		foreach ($list as $car) {

			// Get the Finish name if this car is the first of a new finish
			$prev_tag = $car->prev_sibling();
			if($prev_tag->tag == 'div' &&  $prev_tag->class == 'results-title') {
				$finish_name = $prev_tag->plaintext;
			}

			// Get the info about the car offer
			$image = $car->find('div[class=car-card__visual]', 0)->find('img', 0)->src;
			$serie = $car->find('div[class=car-card__title]', 0)->plaintext;
			$url = $car->find('a', 0)->href;
			// Check if the car model is in stock or available only on order
			if($car->find('span[class*=tag--dispo]', 0) != null) {
				$availability = 'En Stock';
			} else {
				$availability = 'Sur commande';
			}
			$discount_html = $car->find('span[class=promo]', 0);
			// Check if there is any discount dsiplayed
			if ($discount_html != null) {
				$discount = $discount_html->plaintext;
			} else {
				$discount = 'inconnue';
			}
			$price = $car->find('span[class=price]', 0)->plaintext;

			// Construct the new item
			$item = array();
			$item['title'] = $finish_name . ' ' . $serie;
			$item['content'] = '<p><img style="vertical-align:middle ; padding: 10px" src="' . $image . '" />'
				. $finish_name . ' ' . $serie . '</p>';
			$item['content'] .= '<ul><li>Disponibilité : ' . $availability . '</li>';
			$item['content'] .= '<li>Série : ' . $serie . '</li>';
			$item['content'] .= '<li>Remise : ' . $discount . '</li>';
			$item['content'] .= '<li>Prix : ' . $price . '</li></ul>';

			// Add a fictionnal anchor to the RSS element URL, based on the item content ;
			// As the URL could be identical even if the price change, some RSS reader will not show those offers as new items
			$item['uri'] = $url . '#' . md5($item['content']);

			$this->items[] = $item;
		}
	}
}
