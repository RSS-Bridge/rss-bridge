<?php
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
        $link = 'http://www.leboncoin.fr/annonces/offres/' . $param['r'] . '/?f=a&th=1&q=' . urlencode($param['k']);
        $html = file_get_html($link) or $this->returnError('Could not request LeBonCoin.', 404);

        $list = $html->find('.tabsContent', 0);
        if($list === NULL) {
            return;
        }
        
        $tags = $list->find('li');

        foreach($tags as $element) {

            $element = $element->find('a', 0);
            
            $item = new \Item();
            $item->uri = $element->href;
            $title = $element->getAttribute('title');
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
