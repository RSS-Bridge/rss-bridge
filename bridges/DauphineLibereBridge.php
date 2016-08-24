<?php
class DauphineLibereBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "qwertygc";
		$this->name = "Dauphine Bridge";
		$this->uri = "http://www.ledauphine.com/";
		$this->description = "Returns the newest articles.";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'Catégorie de l\'article',
            'type'=>'list',
            'values'=>array(
              'À la une'=>'',
              'France Monde'=>'france-monde',
              'Faits Divers'=>'faits-divers',
              'Économie et Finance'=>'economie-et-finance',
              'Politique'=>'politique',
              'Sport'=>'sport',
              'Ain'=>'ain',
              'Alpes-de-Haute-Provence'=>'haute-provence',
              'Hautes-Alpes'=>'hautes-alpes',
              'Ardèche'=>'ardeche',
              'Drôme'=>'drome',
              'Isère Sud'=>'isere-sud',
              'Savoie'=>'savoie',
              'Haute-Savoie'=>'haute-savoie',
              'Vaucluse'=>'vaucluse'
            )
          )
        );
	}

	private function ExtractContent($url, $context) {
		$html2 = $this->getSimpleHTMLDOM($url,false,$context);
		$text = $html2->find('div.column', 0)->innertext;
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		return $text;
	}

	public function collectData(){
        $param=$this->parameters[$this->queriedContext];

		// Simulate Mozilla user-agent to fix error 403 (Forbidden)
		$opts = array('http' =>
			array(
				'method'  => 'GET',
				'header'  => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
			)
		);

		$context = stream_context_create($opts);

		if (isset($param['u']['value'])) { /* user timeline mode */
			$this->request = $param['u']['value'];
			$html = $this->getSimpleHTMLDOM('http://www.ledauphine.com/'.$this->request.'/rss',false,$context) or $this->returnServerError('Could not request DauphineLibere.');
		}
		else {
			$html = $this->getSimpleHTMLDOM('http://www.ledauphine.com/rss',false,$context) or $this->returnServerError('Could not request DauphineLibere.');
		}
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 10) {
				$item = array();
				$item['title'] = $element->find('title', 0)->innertext;
				$item['uri'] = $element->find('guid', 0)->plaintext;
				$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
				$item['content'] = $this->ExtractContent($item['uri'], $context);
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
