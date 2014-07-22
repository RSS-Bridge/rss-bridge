<?php
/**
* RssBridgeMondeDiplo
* Search MondeDiplo for most recent pages.
* Returns the most recent links in results.
* 2014-07-22
*
* @name MondeDiplo
* @homepage http://www.monde-diplomatique.fr
* @description Returns most recent results from MondeDiplo.
* @maintainer Pitchoule
*/
class MondeDiploBridge extends BridgeAbstract{

    public function collectData(array $param){
        $link = 'http://www.monde-diplomatique.fr';
		
        $html = file_get_html($link) or $this->returnError('Could not request MondeDiplo. for : ' . $link , 404);
	
        foreach($html->find('div[class=grid_10 alpha omega laune]') as $element) {
                $item = new Item();
                $item->uri = 'http://www.monde-diplomatique.fr'.$element->find('a', 0)->href;
                $NumArticle = explode("/", $element->find('a', 0)->href);
                $item->title = $element->find('h3', 0)->plaintext;
                $item->content = $element->find('div[class=crayon article-intro-'.$NumArticle[4].'  intro]', 0)->plaintext;
                $this->items[] = $item;
       }
		
	    foreach($html->find('div.titraille') as $element) {
		            $item = new Item();
                $item->uri = 'http://www.monde-diplomatique.fr'.$element->find('a', 0)->href;
                $item->title = $element->find('h3', 0)->plaintext;
                $item->content = $element->find('div.dates_auteurs', 0)->plaintext;
                $this->items[] = $item;
	}
    }

    public function getName(){
        return 'Monde Diplomatique';
    }

    public function getURI(){
        return 'http://www.monde-diplomatique.fr';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
		
