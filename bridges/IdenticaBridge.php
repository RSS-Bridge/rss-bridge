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
        $html = '';
        $html = $this->getSimpleHTMLDOM($this->getURI())
            or $this->returnServerError('Requested username can\'t be found.');

        foreach($html->find('li.major') as $dent) {
            $item = array();
            $item['uri'] = html_entity_decode($dent->find('a', 0)->href);	// get dent link
            $item['timestamp'] = strtotime($dent->find('abbr.easydate', 0)->plaintext);	// extract dent timestamp
            $item['content'] = trim($dent->find('div.activity-content', 0)->innertext);	// extract dent text
            $item['title'] = $param['u']['value'] . ' | ' . $item['content'];
            $this->items[] = $item;
        }
    }

    public function getName(){
        $param=$this->parameters[$this->queriedContext];
        return $param['u']['value'] .' - Identica Bridge';
    }

    public function getURI(){
        $param=$this->parameters[$this->queriedContext];
        return $this->uri.urlencode($param['u']['value']);
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}
