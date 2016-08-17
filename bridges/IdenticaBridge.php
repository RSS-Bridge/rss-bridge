<?php
class IdenticaBridge extends BridgeAbstract{

	private $request;

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Identica Bridge";
		$this->uri = "https://identi.ca/";
		$this->description = "Returns user timelines";
		$this->update = '2016-08-17';

		$this->parameters[] =
		'[
			{
				"name" : "username",
				"identifier" : "u"
			}
		]';

	}

    public function collectData(array $param){
        $html = '';
        if (isset($param['u'])) {   /* user timeline mode */
        	$this->request = $param['u'];
            $html = $this->file_get_html('https://identi.ca/'.urlencode($this->request)) or $this->returnServerError('Requested username can\'t be found.');
        }
        else {
            $this->returnClientError('You must specify an Identica username (?u=...).');
        }

        foreach($html->find('li.major') as $dent) {
            $item = new \Item();
            $item->uri = html_entity_decode($dent->find('a', 0)->href);	// get dent link
            $item->timestamp = strtotime($dent->find('abbr.easydate', 0)->plaintext);	// extract dent timestamp
            $item->content = trim($dent->find('div.activity-content', 0)->innertext);	// extract dent text
            $item->title = $param['u'] . ' | ' . $item->content;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return (!empty($this->request) ? $this->request .' - ' : '') .'Identica Bridge';
    }

    public function getURI(){
        return 'https://identica.com';
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}
