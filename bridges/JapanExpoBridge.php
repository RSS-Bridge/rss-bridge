<?php
class JapanExpoBridge extends BridgeAbstract{

    public function loadMetadatas() {

        $this->maintainer = "Ginko";
        $this->name = "JapanExpo";
        $this->uri = "http://www.japan-expo-paris.com/fr/actualites";
        $this->description = "Returns most recent results from Japan Expo actualités.";
        $this->update = "2016-01-19";

    }

    public function collectData(array $param){
        $link = 'http://www.japan-expo-paris.com/fr/actualites';
        
        $html = file_get_html($link) or $this->returnError('Could not request JapanExpo. for : ' . $link , 404);
    
        foreach($html->find('a._tile2') as $element) {
            $item = new Item();
            $item->uri = $element->href;
            $item->title = $element->find('span._title', 0)->plaintext;
            $style = $element->find('img.rspvimgset', 0)->style;
            preg_match('/url\(([^)]+)\)/', $style, $match);
            $item->content = "<img src=".$match[1]."></img><br>".$element->find('span.date', 0)->plaintext;
            $this->items[] = $item; 
        }
        
    }

    public function getName(){
        return 'Japan Expo Actualités';
    }

    public function getURI(){
        return 'http://www.japan-expo-paris.com/fr/actualites';
    }

    public function getCacheDuration(){
        return 86400; // 1 day
    }
}
