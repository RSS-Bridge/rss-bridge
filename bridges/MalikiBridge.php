<?php
/**
* RssBridgeMaliki 
* Returns Maliki's strips from previous weeks
*
* @name Maliki
* @description Returns Maliki's strips from previous weeks
*/
class MalikiBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://www.maliki.com/') or $this->returnError('Could not request Maliki.', 404);

        foreach($html->find('div.boite_strip') as $element) {
	  if(!empty($element->find('a',0)->href)) {
            $item = new \Item();
            $item->uri = 'http://www.maliki.com/'.$element->find('a',0)->href;
            $item->thumbnailUri = 'http://www.maliki.com/'.$element->find('img',0)->src;
            $item->title = $element->find('img',0)->title;
            $item->timestamp = strtotime(str_replace('/', '-', $element->find('span.stylepetit', 0)->innertext));
            $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a>';
            $this->items[] = $item;
          }
        }
    }

    public function getName(){
        return 'Maliki';
    }

    public function getURI(){
        return 'http://www.maliki.com/';
    }

    public function getCacheDuration(){
        return 86400; // 24 hours
    }
}
