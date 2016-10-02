<?php
class IdenticaBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Identica Bridge";
	const URI = "https://identi.ca/";
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = "Returns user timelines";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'username',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Requested username can\'t be found.');

        foreach($html->find('li.major') as $dent) {
            $item = array();
            $item['uri'] = html_entity_decode($dent->find('a', 0)->href);	// get dent link
            $item['timestamp'] = strtotime($dent->find('abbr.easydate', 0)->plaintext);	// extract dent timestamp
            $item['content'] = trim($dent->find('div.activity-content', 0)->innertext);	// extract dent text
            $item['title'] = $this->getInput('u') . ' | ' . $item['content'];
            $this->items[] = $item;
        }
    }

    public function getName(){
        return $this->getInput('u') .' - Identica Bridge';
    }

    public function getURI(){
        return self::URI.urlencode($this->getInput('u'));
    }
}
