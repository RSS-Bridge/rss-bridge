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

	public function collectData(){

		$params = array(
			'text' => $this->getInput('k'),
			'region' => $this->getInput('r'),
			'category' => $this->getInput('c'),
			'owner_type' => $this->getInput('o'),
		);

		$url = self::URI . 'recherche/?' . http_build_query($params);
		$html = getContents($url)
			or returnServerError('Could not request LeBonCoin. Tried: ' . $url);

		if(!preg_match('/^<script>window.FLUX_STATE[^\r\n]*/m', $html, $matches)) {
			returnServerError('Could not parse JSON in page content.');
		}

		$clean_match = str_replace(
			array('</script>', '<script>window.FLUX_STATE = '),
			array('', ''),
			$matches[0]
		);
		$json = json_decode($clean_match);

		if($json->adSearch->data->total === 0) {
			return;
		}

		foreach($json->adSearch->data->ads as $element) {

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
}
