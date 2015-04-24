<?php
/**
*
* @name Les Joies Du Code
* @description LesJoiesDuCode via rss-bridge
* @update 30/01/2014
*/
class LesJoiesDuCode extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://lesjoiesducode.fr/') or $this->returnError('Could not request LesJoiesDuCode.', 404);
    
        foreach($html->find('div.post') as $element) {
            $item = new Item();
            $temp = $element->find('h3 a', 0);
            
            $titre = $temp->innertext;
            $url = $temp->href;
            
            $temp = $element->find('div.bodytype', 0);
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
        return 'Les Joies Du Code';
    }

    public function getURI(){
        return 'http://lesjoiesducode.fr/';
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
    public function getDescription(){
        return "Les Joies Du Code via rss-bridge";
    }
}
