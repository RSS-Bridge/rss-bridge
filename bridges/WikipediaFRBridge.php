<?php
/**
* RssBridgeWikipediaFR
* Retrieve latest highlighted articles from Wikipedia in French.
*
* @name Wikipedia FR "Lumière sur..."
* @description Returns the highlighted fr.wikipedia.org article.
*/
class WikipediaFRBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $host = 'http://fr.wikipedia.org';
        // If you want HTTPS access instead, uncomment the following line:
        //$host = 'https://fr.wikipedia.org';
        $link = '/wiki/Wikip%C3%A9dia:Accueil_principal';

        $html = file_get_html($host.$link) or $this->returnError('Could not request Wikipedia FR.', 404);

		$element = $html->find('div[id=accueil-lumieresur]', 0);
		$item = new \Item();
		$item->uri = $host.$element->find('p', 0)->find('a', 0)->href;
		$item->title = $element->find('p',0)->find('a',0)->title;
		$item->content = str_replace('href="/', 'href="'.$host.'/', $element->find('div[id=mf-lumieresur]', 0)->innertext);
		$this->items[] = $item;
    }

    public function getName(){
        return 'Wikipedia FR "Lumière sur..."';
    }

    public function getURI(){
        return 'https://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Accueil_principal';
    }

    public function getCacheDuration(){
        return 3600*4; // 4 hours
    }
}
