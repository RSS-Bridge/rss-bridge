<?php
class DauphineLibereBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "qwertygc";
		$this->name = "Dauphine Bridge";
		$this->uri = "http://www.ledauphine.com/";
		$this->description = "Returns the newest articles.";
		$this->update = "2016-08-09";

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

	private function ExtractContent($url, $context) {
		$html2 = $this->file_get_html($url, false, $context);
		$text = $html2->find('div.column', 0)->innertext;
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		return $text;
	}

	public function collectData(array $param){

		// Simulate Mozilla user-agent to fix error 403 (Forbidden)
		$opts = array('http' =>
			array(
				'method'  => 'GET',
				'header'  => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
			)
		);

		$context = stream_context_create($opts);

		if (isset($param['u'])) { /* user timeline mode */
			$this->request = $param['u'];
			$html = $this->file_get_html('http://www.ledauphine.com/'.$this->request.'/rss', false, $context) or $this->returnError('Could not request DauphineLibere.', 404);
		}
		else {
			$html = $this->file_get_html('http://www.ledauphine.com/rss', false, $context) or $this->returnError('Could not request DauphineLibere.', 404);
		}
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 10) {
				$item = new \Item();
				$item->title = $element->find('title', 0)->innertext;
				$item->uri = $element->find('guid', 0)->plaintext;
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->ExtractContent($item->uri, $context);
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 3600*2; // 2 hours
	}
}
?>