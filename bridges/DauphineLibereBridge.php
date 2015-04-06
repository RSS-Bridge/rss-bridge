<?php
/**
* @name DauphineLibereBridge Bridge
* @homepage http://www.ledauphine.com/
* @description Returns the newest articles. For choice « à la une » leave empty the input. For « France-Monde » input "france-monde". For « Faits Divers » input "faits-divers". For « Economie et Finance » input "economie-et-finance". For « Politique » input "politique". For « Sport » input "sport". For « Ain » input "ain". For « Alpes-de-Haute-Provence  » input "haute-provence". For « Hautes-Alpes » input "hautes-alpes". For « Ardèche » input "ardeche". For « Drôme » input "drome". For « Isere Sud » input "isere-sud". For « Isere Nord » input "isere-nord". For « Savoie » input "savoie". For « Haute-Savoie » input "haute-savoie". For « Vaucluse » input "vaucluse".
* @maintainer qwertygc
* @use1(u="edition")
*/
class DauphineLibereBridge extends BridgeAbstract{
    
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
