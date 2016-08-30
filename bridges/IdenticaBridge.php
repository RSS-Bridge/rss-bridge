<?php
class IdenticaBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Identica Bridge";
	public $uri = "https://identi.ca/";
	public $description = "Returns user timelines";

    public $parameters = array( array(
        'u'=>array(
            'name'=>'username',
            'required'=>true
        )
    ));

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError('Requested username can\'t be found.');

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
        return $this->uri.urlencode($this->getInput('u'));
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}
