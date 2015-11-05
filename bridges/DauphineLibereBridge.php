<?php
class DauphineLibereBridge extends BridgeAbstract{

    	public function loadMetadatas() {

			$this->maintainer = "qwertygc";
			$this->name = "DauphineLibereBridge Bridge";
			$this->uri = "http://www.ledauphine.com/";
			$this->description = "Returns the newest articles.";
			$this->update = "05/11/2015";


			$this->parameters[] =
			'[
				{
					"name" : "Catégorie de l\'article",
					"identifier" : "u",
					"type" : "list",
					"values" : [
						{
							"name" : "À la une",
							"value" : ""
						},
						{
							"name" : "France Monde",
							"value" : "france-monde"
						},
						{
							"name" : "Faits Divers",
							"value" : "faits-divers"
						},
						{
							"name" : "Économie et Finance",
							"value" : "economie-et-finance"
						},
						{
							"name" : "Politique",
							"value" : "politique"
						},
						{
							"name" : "Sport",
							"value" : "sport"
						},
						{
							"name" : "Ain",
							"value" : "ain"
						},
						{
							"name" : "Alpes-de-Haute-Provence",
							"value" : "haute-provence"
						},
						{
							"name" : "Hautes-Alpes",
							"value" : "hautes-alpes"
						},
						{
							"name" : "Ardèche",
							"value" : "ardeche"
						},
						{
							"name" : "Drôme",
							"value" : "drome"
						},
						{
							"name" : "Isère Sud",
							"value" : "isere-sud"
						},
						{
							"name" : "Savoie",
							"value" : "savoie"
						},
						{
							"name" : "Haute-Savoie",
							"value" : "haute-savoie"
						},
						{
							"name" : "Vaucluse",
							"value" : "vaucluse"
						}
					]
				}
			]';
		}


        public function collectData(array $param){

			
		function ExtractContent($url) {
		$html2 = file_get_html($url);
		$text = $html2->find('div.column', 0)->innertext;
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		return $text;
		}
		if (isset($param['u'])) { /* user timeline mode */
			$this->request = $param['u'];
			$html = file_get_html('http://www.ledauphine.com/'.$this->request.'/rss') or $this->returnError('Could not request DauphineLibere.', 404);
		}
		else {
			$html = file_get_html('http://www.ledauphine.com/rss') or $this->returnError('Could not request DauphineLibere.', 404);
		}
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 10) {
		 $item = new \Item();
		 $item->title = $element->find('title', 0)->innertext;
		 $item->uri = $element->find('guid', 0)->plaintext;
		 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
		 $item->content = ExtractContent($item->uri);
		 $this->items[] = $item;
		 $limit++;
		 }
		}
    
    }

    public function getName(){
        return 'Dauphine Bridge';
    }

    public function getURI(){
        return 'http://ledauphine.com/';
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
        // return 0; // 2 hours
    }
}
