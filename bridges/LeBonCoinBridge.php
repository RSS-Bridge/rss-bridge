<?php
class LeBonCoinBridge extends BridgeAbstract {

	const MAINTAINER = 'jacknumber';
	const NAME = 'LeBonCoin';
	const URI = 'https://www.leboncoin.fr/';
	const DESCRIPTION = 'Returns most recent results from LeBonCoin';

	const PARAMETERS = array(
		array(
			'k' => array('name' => 'Mot Clé'),
			'r' => array(
				'name' => 'Région',
				'type' => 'list',
				'values' => array(
					'Toute la France' => '',
					'Alsace' => '1',
					'Aquitaine' => '2',
					'Auvergne' => '3',
					'Basse Normandie' => '4',
					'Bourgogne' => '5',
					'Bretagne' => '6',
					'Centre' => '7',
					'Champagne Ardenne' => '8',
					'Corse' => '9',
					'Franche Comté' => '10',
					'Haute Normandie' => '11',
					'Ile de France' => '12',
					'Languedoc Roussillon' => '13',
					'Limousin' => '14',
					'Lorraine' => '15',
					'Midi Pyrénées' => '16',
					'Nord Pas De Calais' => '17',
					'Pays de la Loire' => '18',
					'Picardie' => '19',
					'Poitou Charentes' => '20',
					'Provence Alpes Côte d\'Azur' => '21',
					'Rhône-Alpes' => '22',
					'Guadeloupe' => '23',
					'Martinique' => '24',
					'Guyane' => '25',
					'Réunion' => '26'
				)
			),
			'cities' => array('name' => 'Ville'),
			'c' => array(
				'name' => 'Catégorie',
				'type' => 'list',
				'values' => array(
					'Toutes catégories' => '',
					'EMPLOI' => array(
						'Emploi et recrutement' => '71',
						'Offres d\'emploi et jobs' => '33'
					),
					'VEHICULES' => array(
						'Tous' => '1',
						'Voitures' => '2',
						'Motos' => '3',
						'Caravaning' => '4',
						'Utilitaires' => '5',
						'Equipement Auto' => '6',
						'Equipement Moto' => '44',
						'Equipement Caravaning' => '50',
						'Nautisme' => '7',
						'Equipement Nautisme' => '51'
					),
					'IMMOBILIER' => array(
						'Tous' => '8',
						'Ventes immobilières' => '9',
						'Locations' => '10',
						'Colocations' => '11',
						'Bureaux & Commerces' => '13'
					),
					'VACANCES' => array(
						'Tous' => '66',
						'Locations & Gîtes' => '12',
						'Chambres d\'hôtes' => '67',
						'Campings' => '68',
						'Hôtels' => '69',
						'Hébergements insolites' => '70'
					),
					'MULTIMEDIA' => array(
						'Tous' => '14',
						'Informatique' => '15',
						'Consoles & Jeux vidéo' => '43',
						'Image & Son' => '16',
						'Téléphonie' => '17'
					),
					'LOISIRS' => array(
						'Tous' => '24',
						'DVD / Films' => '25',
						'CD / Musique' => '26',
						'Livres' => '27',
						'Animaux' => '28',
						'Vélos' => '55',
						'Sports & Hobbies' => '29',
						'Instruments de musique' => '30',
						'Collection' => '40',
						'Jeux & Jouets' => '41',
						'Vins & Gastronomie' => '48'
					),
					'MATERIEL PROFESSIONNEL' => array(
						'Tous' => '56',
						'Matériel Agricole' => '57',
						'Transport - Manutention' => '58',
						'BTP - Chantier Gros-oeuvre' => '59',
						'Outillage - Matériaux 2nd-oeuvre' => '60',
						'Équipements Industriels' => '32',
						'Restauration - Hôtellerie' => '61',
						'Fournitures de Bureau' => '62',
						'Commerces & Marchés' => '63',
						'Matériel Médical' => '64'
					),
					'SERVICES' => array(
						'Tous' => '31',
						'Prestations de services' => '34',
						'Billetterie' => '35',
						'Evénements' => '49',
						'Cours particuliers' => '36',
						'Covoiturage' => '65'
					),
					'MAISON' => array(
						'Tous' => '18',
						'Ameublement' => '19',
						'Electroménager' => '20',
						'Arts de la table' => '45',
						'Décoration' => '39',
						'Linge de maison' => '46',
						'Bricolage' => '21',
						'Jardinage' => '52',
						'Vêtements' => '22',
						'Chaussures' => '53',
						'Accessoires & Bagagerie' => '47',
						'Montres & Bijoux' => '42',
						'Equipement bébé' => '23',
						'Vêtements bébé' => '54',
					),
					'AUTRES' => '37'
				)
			),
			'o' => array(
				'name' => 'Vendeur',
				'type' => 'list',
				'values' => array(
					'Tous' => '',
					'Particuliers' => 'private',
					'Professionnels' => 'pro',
				)
			)
		)
	);

	public static $LBC_API_KEY = 'ba0c2dad52b3ec';

	public function collectData(){

		$url = 'https://api.leboncoin.fr/finder/search/';
		$data = $this->buildRequestJson();

		$header = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data),
			'api_key: ' . self::$LBC_API_KEY
		);

		$opts = array(
			CURL_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $data

		);

		$content = getContents($url, $header, $opts)
			or returnServerError('Could not request LeBonCoin. Tried: ' . $url);

		$json = json_decode($content);

		if($json->total === 0) {
			return;
		}

		foreach($json->ads as $element) {

			$item['title'] = $element->subject;
			$item['content'] = $element->body;
			$item['date'] = $element->index_date;
			$item['timestamp'] = strtotime($element->index_date);
			$item['uri'] = $element->url;
			$item['ad_type'] = $element->ad_type;
			$item['author'] = $element->owner->name;

			if(isset($element->location->city)) {

				$item['city'] = $element->location->city;
				$item['content'] .= ' -- ' . $element->location->city;

			}

			if(isset($element->location->zipcode)) {
				$item['zipcode'] = $element->location->zipcode;
			}

			if(isset($element->price)) {

				$item['price'] = $element->price[0];
				$item['content'] .= ' -- ' . current($element->price) . '€';

			}

			if(isset($element->images->urls)) {

				$item['thumbnail'] = $element->images->thumb_url;
				$item['enclosures'] = array();

				foreach($element->images->urls as $image) {
					$item['enclosures'][] = $image;
				}

			}

			$this->items[] = $item;
		}
	}


	private function buildRequestJson() {

		$requestJson = new StdClass();
		$requestJson->owner_type = $this->getInput('o');
		$requestJson->filters->location = array();
		if($this->getInput('r') != '') {
			$requestJson->filters->location['regions'] = [$this->getInput('r')];
		}
		if($this->getInput('cities') != '') {
			$requestJson->filters->location['city_zipcodes'] = [$this->getInput('cities')];
		}

		$requestJson->filters->category = array(
							'id' => $this->getInput('c')
		);

		$requestJson->filters->keywords = array(
							'text' => $this->getInput('k')
		);

		$requestJson->limit = 30;

		return json_encode($requestJson);

	}



}
