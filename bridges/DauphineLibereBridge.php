<?php
/**
* @name DauphineLibereBridge Bridge
* @homepage http://www.ledauphine.com/
* @description Returns the newest articles.
* @maintainer qwertygc
* @use1(list|u="À la une=>;France Monde=>france-monde;Faits Divers=>faits-divers;Économie et Finance =>economie-et-finance;Politique=>politique;Sport=>sport;Ain=>ain;Alpes-de-Haute-Provence=>haute-provence;Hautes-Alpes=>hautes-alpes;Ardèche=>ardeche;Drôme=>drome;Isère Sud=>isere-sud;Isère Nord=>isere-nord;Savoie=>savoie;Haute-Savoie=>haute-savoie;Vaucluse=>vaucluse")
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
