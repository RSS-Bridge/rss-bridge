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
    
        foreach($html->find('div.laune') as $element) {
            $item = new Item();
            $item->uri = 'http://www.monde-diplomatique.fr'.$element->find('a', 0)->href;
            $item->title = $element->find('h3', 0)->plaintext;
            $item->content = $element->find('div.dates_auteurs', 0)->plaintext. '<br>' .strstr($element->find('div', 0)->plaintext, $element->find('div.dates_auteurs', 0)->plaintext, true);
            $this->items[] = $item;
        }

        $liste = $html->find('div.listes', 0); // First list
        foreach ($liste->find('li') as $e) {
            
            $item = new Item();
            $item->uri = 'http://www.monde-diplomatique.fr' . $e->find('a', 0)->href;
            $item->title = $e->find('a', 0)->plaintext;
            $item->content = $e->find('div.dates_auteurs', 0)->plaintext;
            $this->items[] = $item;
        }

        foreach($html->find('div.liste ul li') as $element) {
            if ($element->getAttribute('class') != 'intrapub') {
                 $item = new Item();
                $item->uri = 'http://www.monde-diplomatique.fr'.$element->find('a', 0)->href;
                $item->title = $element->find('h3', 0)->plaintext;
                $item->content = $element->find('div.dates_auteurs', 0)->plaintext . ' <br> ' . $element->find('div.intro', 0)->plaintext;
                $this->items[] = $item;
            }
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
        
<<<<<<< HEAD
=======

>>>>>>> bf2303ead8b6c8aa01a3e1c58ddd27cbc4fc2d71
