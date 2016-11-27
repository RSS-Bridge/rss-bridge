<?php
class BlaguesDeMerdeBridge extends BridgeAbstract{

    const MAINTAINER = "superbaillot.net";
    const NAME = "Blagues De Merde";
    const URI = "http://www.blaguesdemerde.fr/";
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = "Blagues De Merde";


    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request BDM.');

        foreach($html->find('article.joke_contener') as $element) {
            $item = array();
            $temp = $element->find('a');
            if(isset($temp[2]))
            {
                $item['content'] = trim($element->find('div.joke_text_contener', 0)->innertext);
                $uri = $temp[2]->href;
                $item['uri'] = $uri;
                $item['title'] = substr($uri, (strrpos($uri, "/") + 1));
                $date = $element->find("li.bdm_date",0)->innertext;
                $time = mktime(0, 0, 0, substr($date, 3, 2), substr($date, 0, 2), substr($date, 6, 4));
                $item['timestamp'] = $time;
                $item['author'] = $element->find("li.bdm_pseudo",0)->innertext;;
                $this->items[] = $item;
            }
        }
    }
}
?>
