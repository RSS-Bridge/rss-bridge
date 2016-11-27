<?php
class LeBonCoinBridge extends BridgeAbstract{

	const MAINTAINER = "16mhz";
	const NAME = "LeBonCoin";
	const URI = "http://www.leboncoin.fr/";
	const DESCRIPTION = "Returns most recent results from LeBonCoin for a region, and optionally a category and a keyword .";

    const PARAMETERS = array( array(
          'k'=>array('name'=>'Mot Clé'),
          'r'=>array(
            'name'=>'Région',
            'type'=>'list',
            'values'=>array(
              'Toute la France'=>'ile_de_france/occasions',
              'Alsace'=>'alsace',
              'Aquitaine'=>'aquitaine',
              'Auvergne'=>'auvergne',
              'Basse Normandie'=>'basse_normandie',
              'Bourgogne'=>'bourgogne',
              'Bretagne'=>'bretagne',
              'Centre'=>'centre',
              'Champagne Ardenne'=>'champagne_ardenne',
              'Corse'=>'corse',
              'Franche Comté'=>'franche_comte',
              'Haute Normandie'=>'haute_normandie',
              'Ile de France'=>'ile_de_france',
              'Languedoc Roussillon'=>'languedoc_roussillon',
              'Limousin'=>'limousin',
              'Lorraine'=>'lorraine',
              'Midi Pyrénées'=>'midi_pyrenees',
              'Nord Pas De Calais'=>'nord_pas_de_calais',
              'Pays de la Loire'=>'pays_de_la_loire',
              'Picardie'=>'picardie',
              'Poitou Charentes'=>'poitou_charentes',
              'Provence Alpes Côte d\'Azur'=>'provence_alpes_cote_d_azur',
              'Rhône-Alpes'=>'rhone_alpes',
              'Guadeloupe'=>'guadeloupe',
              'Martinique'=>'martinique',
              'Guyane'=>'guyane',
              'Réunion'=>'reunion'
            )
          ),
          'c'=>array(
            'name'=>'Catégorie',
            'type'=>'list',
            'values'=>array(
              'TOUS'=>'',
              'EMPLOI'=>'_emploi_',
              'VEHICULES'=>array(
                'Tous'=>'_vehicules_',
                'Voitures'=>'voitures',
                'Motos'=>'motos',
                'Caravaning'=>'caravaning',
                'Utilitaires'=>'utilitaires',
                'Équipement Auto'=>'equipement_auto',
                'Équipement Moto'=>'equipement_moto',
                'Équipement Caravaning'=>'equipement_caravaning',
                'Nautisme'=>'nautisme',
                'Équipement Nautisme'=>'equipement_nautisme'
              ),
              'IMMOBILIER'=>array(
                'Tous'=>'_immobilier_',
                'Ventes immobilières'=>'ventes_immobilieres',
                'Locations'=>'locations',
                'Colocations'=>'colocations',
                'Bureaux & Commerces'=>'bureaux_commerces'
              ),
              'VACANCES'=>array(
                'Tous'=>'_vacances_',
                'Location gîtes'=>'locations_gites',
                'Chambres d\'hôtes'=>'chambres_d_hotes',
                'Campings'=>'campings',
                'Hôtels'=>'hotels',
                'Hébergements insolites'=>'hebergements_insolites'
              ),
              'MULTIMEDIA'=>array(
                'Tous'=>'_multimedia_',
                'Informatique'=>'informatique',
                'Consoles & Jeux vidéo'=>'consoles_jeux_video',
                'Image & Son'=>'image_son',
                'Téléphonie'=>'telephonie'
              ),
              'LOISIRS'=>array(
                'Tous'=>'_loisirs_',
                'DVD / Films'=>'dvd_films',
                'CD / Musique'=>'cd_musique',
                'Livres'=>'livres',
                'Animaux'=>'animaux',
                'Vélos'=>'velos',
                'Sports & Hobbies'=>'sports_hobbies',
                'Instruments de musique'=>'instruments_de_musique',
                'Collection'=>'collection',
                'Jeux & Jouets'=>'jeux_jouets',
                'Vins & Gastronomie'=>'vins_gastronomie'
              ),
              'MATÉRIEL PROFESSIONNEL'=>array(
                'Tous'=>'_materiel_professionnel_',
                'Matériel Agricole'=>'mateiel_agricole',
                'Transport - Manutention'=>'transport_manutention',
                'BTP - Chantier - Gros-œuvre'=>'btp_chantier_gros_oeuvre',
                'Outillage - Matériaux 2nd-œuvre'=>'outillage_materiaux_2nd_oeuvre',
                'Équipements Industriels'=>'equipement_industriels',
                'Restauration - Hôtellerie'=>'restauration_hotellerie',
                'Fournitures de Bureau'=>'fournitures_de_bureau',
                'Commerces & Marchés'=>'commerces_marches',
                'Matériel médical'=>'materiel_medical'
              ),
              'SERVICES'=>array(
                'Tous'=>'_services_',
                'Prestations de services'=>'prestations_de_services',
                'Billetterie'=>'billetterie',
                'Évènements'=>'evenements',
                'Cours particuliers'=>'cours_particuliers',
                'Covoiturage'=>'covoiturage'
              ),
              'MAISON'=>array(
                'Tous'=>'_maison_',
                'Ameublement'=>'ameublement',
                'Électroménager'=>'electromenager',
                'Arts de la table'=>'arts_de_la_table',
                'Décoration'=>'decoration',
                'Linge de maison'=>'linge_de_maison',
                'Bricolage'=>'bricolage',
                'Jardinage'=>'jardinage',
                'Vêtements'=>'vetements',
                'Chaussures'=>'chaussures',
                'Accessoires & Bagagerie'=>'accessoires_bagagerie',
                'Montres & Bijoux'=>'montres_bijoux',
                'Équipement bébé'=>'equipement_bebe',
                'Vêtements bébé'=>'vetements_bebe'
              ),
              'AUTRES'=>'autres'
            )
        )
      )
  );

	public function collectData(){

        $category=$this->getInput('c');
        if (empty($category)){
            $category='annonces';
        }

        $html = getSimpleHTMLDOM(
            self::URI.$category.'/offres/' . $this->getInput('r') . '/?'
            .'f=a&th=1&'
            .'q=' . urlencode($this->getInput('k'))
        ) or returnServerError('Could not request LeBonCoin.');

		$list = $html->find('.tabsContent', 0);
		if($list === NULL) {
			return;
		}

		$tags = $list->find('li');

		foreach($tags as $element) {

			$element = $element->find('a', 0);

			$item = array();
			$item['uri'] = $element->href;
			$title = html_entity_decode($element->getAttribute('title'));
			$content_image = $element->find('div.item_image', 0)->find('.lazyload', 0);

			if($content_image !== NULL) {
				$content = '<img src="' . $content_image->getAttribute('data-imgsrc') . '" alt="thumbnail">';
			} else {
				$content = "";
			}
			$date = $element->find('aside.item_absolute', 0)->find('p.item_sup', 0);

			$detailsList = $element->find('section.item_infos', 0);

			for($i = 0; $i <= 1; $i++) $content .= $detailsList->find('p.item_supp', $i)->plaintext;
			$price = $detailsList->find('h3.item_price', 0);
			$content .= $price === NULL ? '' : $price->plaintext;

			$item['title'] = $title;
			$item['content'] = $content . $date;
			$this->items[] = $item;
		}
	}
}
