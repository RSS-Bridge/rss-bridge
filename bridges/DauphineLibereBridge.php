<?php
class DauphineLibereBridge extends BridgeAbstract {

	public $maintainer = "qwertygc";
	public $name = "Dauphine Bridge";
	public $uri = "http://www.ledauphine.com/";
	public $description = "Returns the newest articles.";

    public $parameters = array( array(
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
    ));

	private function ExtractContent($url, $context) {
		$html2 = $this->getSimpleHTMLDOM($url);
		$text = $html2->find('div.column', 0)->innertext;
		$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
		return $text;
	}

	public function collectData(){

		$context = stream_context_create($opts);

        $category=$this->getInput('u');
		if (empty($category)) {
            $html = $this->getSimpleHTMLDOM($this->uri.$category.'/rss')
                or $this->returnServerError('Could not request DauphineLibere.');
		} else {
            $html = $this->getSimpleHTMLDOM($this->uri.'rss')
                or $this->returnServerError('Could not request DauphineLibere.');
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
