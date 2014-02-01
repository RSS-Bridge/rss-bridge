<?php
/**
* RssBridgeDansTonChat
* Retrieve lastest quotes from DansTonChat.
* Returns the most recent quotes, sorting by date (most recent first).
*
* @name DansTonChat Bridge
* @description Returns latest quotes from DansTonChat.
*/
class DansTonChatBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $link = 'http://danstonchat.com/latest.html';

        $html = file_get_html($link) or $this->returnError('Could not request DansTonChat.', 404);

        foreach($html->find('div.item') as $element) {
                $item = new \Item();
                $item->uri = $element->find('a', 0)->href;
                $item->title = 'DansTonChat '.$element->find('a', 1)->plaintext;
                $item->content = $element->find('a', 0)->innertext;
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'DansTonChat';
    }

    public function getURI(){
        return 'http://danstonchat.com';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
