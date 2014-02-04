<?php
/**
*
* @name Sexactu
* @description Sexactu via rss-bridge
* @update 04/02/2014
*/
class LesJoiesDuCodeBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://http://www.gqmagazine.fr/sexactu') or $this->returnError('Could not request http://www.gqmagazine.fr/sexactu.', 404);
    
        foreach($html->find('div.content-holder ul li') as $element) {
            $item = new Item();
            $temp = $element->find('h3 a', 0);
            
            $titreElement = $element->find('.title-holder .article-title a');
	$titre = $titreElement->
            $url = $temp->href;
            
            $temp = $element->find('div.text-container', 0);
            $content = $temp->innertext;
            
            $auteur = $temp->find('.c1 em', 0);
            $pos = strpos($auteur->innertext, "by");
            
            if($pos > 0)
            {
                $auteur = trim(str_replace("*/", "", substr($auteur->innertext, ($pos + 2))));
                $item->name = $auteur;
            }
            
            
            $item->content .= trim($content);
            $item->uri = $url;
            $item->title = trim($titre);
            
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Sexactu';
    }

    public function getURI(){
        return 'http://http://www.gqmagazine.fr/sexactu/';
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
    public function getDescription(){
        return "Sexactu via rss-bridge";
    }
}
?>

