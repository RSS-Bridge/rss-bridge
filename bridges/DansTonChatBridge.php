<?php
class DansTonChatBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Astalaseven";
		$this->name = "DansTonChat Bridge";
		$this->uri = "http://danstonchat.com";
		$this->description = "Returns latest quotes from DansTonChat.";

	}

    public function collectData(){
        $html = '';
        $link = 'http://danstonchat.com/latest.html';

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request DansTonChat.');

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
