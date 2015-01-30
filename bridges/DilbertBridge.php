<?php
/**
*
* @name Dilbert Daily Strip
* @homepage http://dilbert.com/strips/
* @description The Unofficial Dilbert Daily Comic Strip
* @update 16/10/2013
* initial maintainer: superbaillot.net
*/
class DilbertBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://dilbert.com/strips/') or $this->returnError('Could not request Dilbert.', 404);

        foreach($html->find('section.comic-item') as $element) {
            $comic = $element->find('img', 0);

            $item = new Item();
            $item->uri = $element->find('a',0)->href;
            $item->content = '<img src="'. $comic->src . '" alt="' . $comic->alt . '" />';
            $item->title = $comic->alt;
            $item->timestamp = strtotime($element->find('h3', 0)->plaintext);
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Dilbert';
    }

    public function getURI(){
        return 'http://dilbert.com';
    }

    public function getDescription(){
        return 'Dilbert via rss-bridge';
    }

    public function getCacheDuration(){
        return 14400; // 4 hours
    }
}
?>
