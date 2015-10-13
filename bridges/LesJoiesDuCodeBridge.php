<?php
/**
*
* @name Les Joies Du Code
* @homepage http://lesjoiesducode.fr/
* @description LesJoiesDuCode
* @update 04/02/2015
* initial maintainer: superbaillot.net
*/
class LesJoiesDuCodeBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://lesjoiesducode.fr/') or $this->returnError('Could not request LesJoiesDuCode.', 404);
    
        foreach($html->find('div.blog-post') as $element) {
            $item = new Item();
            $temp = $element->find('h1 a', 0);
            $titre = html_entity_decode($temp->innertext);
            $url = $temp->href;
            
            $temp = $element->find('div.blog-post-content', 0);

            // retrieve .gif instead of static .jpg
            $images = $temp->find('p img');
            foreach($images as $image){
              $img_src = str_replace(".jpg",".gif",$image->src);
              $image->src = $img_src;
            }
            $content = $temp->innertext;
            
            $auteur = $temp->find('i', 0);
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
?>
