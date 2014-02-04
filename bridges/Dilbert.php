<?php
/**
*
* @name Dilbert Daily Strip 
* @description The Unofficial Dilbert Daily Comic Strip RSS Feed via rss-bridge
* @update 16/10/2013
*/
class DilbertBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://dilbert.com/strips/') or $this->returnError('Could not request Dilbert.', 404);
    
        foreach($html->find('div.STR_Image') as $element) {
            $item = new Item();
            $href = $element->find('a',0)->href;
            $item->uri = 'http://dilbert.com' . $href;
            $content = str_replace('src="/', 'src="http://dilbert.com/',$element->innertext);
            $content = str_replace('href="/', 'href="http://dilbert.com/',$content);
            $item->content = $content;
            $time = strtotime(substr($href, (strrpos($href, "/", -10) + 1), 10));
            $item->title = date("d/m/Y", $time);
            $item->timestamp = $time;
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
