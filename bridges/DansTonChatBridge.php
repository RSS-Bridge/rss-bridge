<?php

class DansTonChatBridge extends BridgeAbstract
{
    const MAINTAINER = 'Astalaseven';
    const NAME = 'DansTonChat Bridge';
    const URI = 'https://danstonchat.com/';
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'Returns latest quotes from DansTonChat.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . 'latest.html');

        foreach ($html->find('div.item') as $element) {
            $item = [];
            $item['uri'] = $element->find('a', 0)->href;
            $titleContent = $element->find('h3 a', 0);
            if ($titleContent) {
                $item['title'] = 'DansTonChat ' . html_entity_decode($titleContent->plaintext, ENT_QUOTES);
            } else {
                $item['title'] = 'DansTonChat';
            }
            $item['content'] = $element->find('div.item-content a', 0)->innertext;
            $this->items[] = $item;
        }
    }
}
