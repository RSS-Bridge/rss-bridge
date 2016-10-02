<?php
class DansTonChatBridge extends BridgeAbstract{

	const MAINTAINER = "Astalaseven";
	const NAME = "DansTonChat Bridge";
	const URI = "http://danstonchat.com/";
	const CACHE_TIMEOUT = 21600; //6h
	const DESCRIPTION = "Returns latest quotes from DansTonChat.";

    public function collectData(){

        $html = getSimpleHTMLDOM(self::URI.'latest.html')
            or returnServerError('Could not request DansTonChat.');

        foreach($html->find('div.item') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = 'DansTonChat '.$element->find('a', 1)->plaintext;
                $item['content'] = $element->find('a', 0)->innertext;
                $this->items[] = $item;
        }
    }
}
