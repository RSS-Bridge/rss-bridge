<?php
class DansTonChatBridge extends BridgeAbstract{

	const MAINTAINER = "Astalaseven";
	const NAME = "DansTonChat Bridge";
	const URI = "http://danstonchat.com/";
	const DESCRIPTION = "Returns latest quotes from DansTonChat.";

    public function collectData(){

        $html = $this->getSimpleHTMLDOM(self::URI.'latest.html')
            or $this->returnServerError('Could not request DansTonChat.');

        foreach($html->find('div.item') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = 'DansTonChat '.$element->find('a', 1)->plaintext;
                $item['content'] = $element->find('a', 0)->innertext;
                $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
