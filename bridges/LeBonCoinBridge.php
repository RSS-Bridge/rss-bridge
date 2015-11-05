<?php
/**
* RssBridgeLeBonCoin
* Search LeBonCoin for most recent ads in a specific region and topic.
* Returns the most recent classified ads in results, sorting by date (most recent first).
*
* @name LeBonCoin
* @homepage http://www.leboncoin.fr
* @description Returns most recent results from LeBonCoin for a region and a keyword.
* @maintainer 16mhz
* @update 2015-10-30
* @use1(list|r="Alsace=>alsace;Aquitaine=>aquitaine;Auvergne=>auvergne;Basse Normandie=>basse_normandie;Bourgogne=>bourgogne;Bretagne=>bretagne;Centre=>centre;Champagne Ardenne=>champagne_ardenne;Corse=>corse;Franche Comté=>franche_comte;Haute Normandie=>haute_normandie;Ile de France=>ile_de_france;Languedoc Roussillon=>languedoc_roussillon;Limousin=>limousin;Lorraine=>lorraine;Midi Pyrénées=>midi_pyrenees;Nord Pas De Calais=>nord_pas_de_calais;Pays de la Loire=>pays_de_la_loire;Picardie=>picardie;Poitou Charentes=>poitou_charentes;Provence Alpes Côte d'Azur=>provence_alpes_cote_d_azur;Rhône-Alpes=>rhone_alpes;Guadeloupe=>guadeloupe;Martinique=>martinique;Guyane=>guyane;Réunion=>reunion", text|k="Keyword")
*/

class LeBonCoinBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "16mhz";
		$this->name = "LeBonCoin";
		$this->uri = "http://www.leboncoin.fr";
		$this->description = "Returns most recent results from LeBonCoin for a region and a keyword.";
		$this->update = "2015-10-30";

		$this->parameters[] =
		'[
			{
				"name" : "Keyword",
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
			}
		]';

	}


    public function collectData(array $param){

        $html = '';
        $link = 'http://www.leboncoin.fr/annonces/offres/' . $param[r] . '/?f=a&th=1&q=' . $param[k];
        $html = file_get_html($link) or $this->returnError('Could not request LeBonCoin.', 404);

        $list = $html->find('.list-lbc', 0);
        if($list === NULL) {
            return;
        }
        $tags = $list->find('a');

        foreach($tags as $element) {
                $item = new \Item();
                $item->uri = $element->href;
                $title = $element->getAttribute('title');
                $content_image = $element->find('div.image', 0)->find('img', 0);

                if($content_image !== NULL) {
                        $content = '<img src="' . $element->find('div.image', 0)->find('img', 0)->getAttribute('src') . '" alt="thumbnail">';
                }
                $date = $element->find('div.date', 0)->find('div', 0) . $element->find('div.date', 0)->find('div', 1) . '<br/>';
                $detailsList = $element->find('div.detail', 0);

                for ($i = 1; $i < 4; $i++) {
                    $line = $detailsList->find('div', $i);
                    $content .= $line;
                }

                $item->title = $title . ' - ' . $detailsList->find('div', 3);
                $item->content = $content . $date;
                $this->items[] = $item;
        }
  }

    public function getName(){
        return 'LeBonCoin';
    }

    public function getURI(){
        return 'http://www.leboncoin.fr';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
