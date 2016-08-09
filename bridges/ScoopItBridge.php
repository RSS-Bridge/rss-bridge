<?php
class ScoopItBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Pitchoule";
		$this->name = "ScoopIt";
		$this->uri = "http://www.scoop.it";
		$this->description = "Returns most recent results from ScoopIt.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "keyword",
				"identifier" : "u"
			}
		]';

	}

    public function collectData(array $param){
        $html = '';
        if ($param['u'] != '') {
            $this->request = $param['u'];
            $link = 'http://scoop.it/search?q=' .urlencode($this->request);
            
            $html = $this->file_get_html($link) or $this->returnError('Could not request ScoopIt. for : ' . $link , 404);
            
            foreach($html->find('div.post-view') as $element) {
                $item = new Item();
                $item->uri = $element->find('a', 0)->href;
                $item->title = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_title',0)->plaintext);
                $item->content = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_description', 0)->plaintext);
                $this->items[] = $item;
            }
        } else {
            $this->returnError('You must specify a keyword', 404);
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

