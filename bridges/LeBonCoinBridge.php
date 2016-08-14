<?php
class LeBonCoinBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "16mhz";
		$this->name = "LeBonCoin";
		$this->uri = "http://www.leboncoin.fr";
		$this->description = "Returns most recent results from LeBonCoin for a region, and optionally a category and a keyword .";
		$this->update = "2016-08-09";

		$this->parameters[] =
            '[

			{
				"name" : "Mot Clé",
				"identifier" : "k"
			},

			{
				"name" : "Région",
				"identifier" : "r",
				"type" : "list",
				"values" : [
						{
						"name" : "Alsace",
						"value" : "alsace"
						},
						{
						"name" : "Aquitaine",
						"value" : "aquitaine"
						},
						{
						"name" : "Auvergne",
						"value" : "auvergne"
						},
						{
						"name" : "Basse Normandie",
						"value" : "basse_normandie"
						},
						{
						"name" : "Bourgogne",
						"value" : "bourgogne"
						},
						{
						"name" : "Bretagne",
						"value" : "bretagne"
						},
						{
						"name" : "Centre",
						"value" : "centre"
						},
						{
						"name" : "Champagne Ardenne",
						"value" : "champagne_ardenne"
						},
						{
						"name" : "Corse",
						"value" : "corse"
						},
						{
						"name" : "Franche Comté",
						"value" : "franche_comte"
						},
						{
						"name" : "Haute Normandie",
						"value" : "haute_normandie"
						},
						{
						"name" : "Ile de France",
						"value" : "ile_de_france"
						},
						{
						"name" : "Languedoc Roussillon",
						"value" : "languedoc_roussillon"
						},
						{
						"name" : "Limousin",
						"value" : "limousin"
						},
						{
						"name" : "Lorraine",
						"value" : "lorraine"
						},
						{
						"name" : "Midi Pyrénées",
						"value" : "midi_pyrenees"
						},
						{
						"name" : "Nord Pas De Calais",
						"value" : "nord_pas_de_calais"
						},
						{
						"name" : "Pays de la Loire",
						"value" : "pays_de_la_loire"
						},
						{
						"name" : "Picardie",
						"value" : "picardie"
						},
						{
						"name" : "Poitou Charentes",
						"value" : "poitou_charentes"
						},
						{
						"name" : "Provence Alpes Côte d\'Azur",
						"value" : "provence_alpes_cote_d_azur"
						},
						{
						"name" : "Rhône-Alpes",
						"value" : "rhone_alpes"
						},
						{
						"name" : "Guadeloupe",
						"value" : "guadeloupe"
						},
						{
						"name" : "Martinique",
						"value" : "martinique"
						},
						{
						"name" : "Guyane",
						"value" : "guyane"
						},
						{
						"name" : "Réunion",
						"value" : "reunion"
						}
				]
			},
			{
				"name" : "Catégorie",
				"identifier" : "c",
				"type" : "list",
				"values" : [
						{ "name" : "---- Select ----", "value" : "" },
						
						{ "name" : "", "value" : "" },
						{ "name" : "EMPLOI", "value" : "_emploi_" },
						
						{ "name" : "", "value" : "" },
						{ "name" : "VEHICULES", "value" : "_vehicules_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Voitures", "value" : "voitures" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Motos", "value" : "motos" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Caravaning", "value" : "caravaning" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Utilitaires", "value" : "utilitaires" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipement Auto", "value" : "equipement_auto" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipement Moto", "value" : "equipement_moto" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipement Caravaning", "value" : "equipement_caravaning" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Nautisme", "value" : "nautisme" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipement Nautisme", "value" : "equipement_nautisme" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "IMMOBILIER", "value" : "_immobilier_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Ventes immobilieres", "value" : "ventes_immobilieres" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Locations", "value" : "locations" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Colocations", "value" : "colocations" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Bureaux &amp; Commerces", "value" : "bureaux_commerces" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "VACANCES", "value" : "_vacances_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Locations gites", "value" : "locations_gites" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Chambres d\'hôtes", "value" : "chambres_d_hotes" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Campings", "value" : "campings" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Hôtels", "value" : "hotels" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Hébergements insolites", "value" : "hebergements_insolites" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "MULTIMEDIA", "value" : "_multimedia_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Informatique", "value" : "informatique" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Consoles & Jeux vidéo", "value" : "consoles_jeux_video" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Image & Son", "value" : "image_son" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Téléphonie", "value" : "telephonie" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "LOISIRS", "value" : "_loisirs_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;DVD / Films", "value" : "dvd_films" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;CD / Musique", "value" : "cd_musique" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Livres", "value" : "livres" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Animaux", "value" : "animaux" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Vélos", "value" : "velos" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Sports & Hobbies", "value" : "sports_hobbies" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Instruments de musique", "value" : "instruments_de_musique" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Collection", "value" : "collection" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Jeux & Jouets", "value" : "jeux_jouets" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Vins & Gastronomie", "value" : "vins_gastronomie" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "MATERIEL PROFESSIONNEL", "value" : "_materiel_professionnel_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Materiel Agricole", "value" : "materiel_agricole" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Transport - Manutention", "value" : "transport_manutention" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;BTP - Chantier - Gros-oeuvre", "value" : "btp_chantier_gros_oeuvre" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Outillage - Materiaux 2nd-oeuvre", "value" : "outillage_materiaux_2nd_oeuvre" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipements Industriels", "value" : "equipements_industriels" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Restauration - Hôtellerie", "value" : "restauration_hotellerie" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Fournitures de Bureau", "value" : "fournitures_de_bureau" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Commerces & Marchés", "value" : "commerces_marches" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Matériel médical", "value" : "materiel_medical" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "SERVICES", "value" : "_services_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Prestations de services", "value" : "prestations_de_services" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Billetterie", "value" : "billetterie" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Evénements", "value" : "evenements" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Cours particuliers", "value" : "cours_particuliers" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Covoiturage", "value" : "covoiturage" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "MAISON", "value" : "_maison_" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Ameublement", "value" : "ameublement" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Electroménager", "value" : "electromenager" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Arts de la table", "value" : "arts_de_la_table" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Décoration", "value" : "decoration" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Linge de maison", "value" : "linge_de_maison" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Bricolage", "value" : "bricolage" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Jardinage", "value" : "jardinage" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Vêtements", "value" : "vetements" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Chaussures", "value" : "chaussures" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Accessoires & Bagagerie", "value" : "accessoires_bagagerie" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Montres & Bijoux", "value" : "montres_bijoux" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Equipement bébé", "value" : "equipement_bebe" },
							{ "name" : "&nbsp;&nbsp;&nbsp;&nbsp;Vêtements bébé", "value" : "vetements_bebe" },
							
						{ "name" : "", "value" : "" },
						{ "name" : "AUTRES", "value" : "autres" }
				]
			}
		]';

	}


	public function collectData(array $param){

		$html = '';
		if (empty($param['c'])) {
			$link = 'http://www.leboncoin.fr/annonces/offres/' . $param['r'] . '/?f=a&th=1&q=' . urlencode($param['k']);
		}
		else {
			$link = 'http://www.leboncoin.fr/' . $param['c'] . '/offres/' . $param['r'] . '/?f=a&th=1&q=' . urlencode($param['k']);
		}
		$html = $this->file_get_html($link) or $this->returnError('Could not request LeBonCoin.', 404);

		$list = $html->find('.tabsContent', 0);
		if($list === NULL) {
			return;
		}

		$tags = $list->find('li');

		foreach($tags as $element) {

			$element = $element->find('a', 0);

			$item = new \Item();
			$item->uri = $element->href;
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

			$item->title = $title;
			$item->content = $content . $date;
			$this->items[] = $item;
		}
	}
}