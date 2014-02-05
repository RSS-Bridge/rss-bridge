<?php
/**
*
* @name Sexactu
* @description Sexactu via rss-bridge
* @update 04/02/2014
*/
class SexactuBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://http://www.gqmagazine.fr/sexactu') or $this->returnError('Could not request http://www.gqmagazine.fr/sexactu.', 404);
    
        foreach($html->find('div.content-holder ul li') as $element) {
            $item = new Item();
            
            // various metadata
            $titleBock  = $element->find('title-holder');
            $titleData = $titleBlock->find('article-title h2 a');
            
            $item->title = trim($titleData->innertext);
            $item->uri = $titleData->href;
            $item->name = "Maïa Mazaurette";
            $item->content = $element->find('text-container')->innertext;
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

