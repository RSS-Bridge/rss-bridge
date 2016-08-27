<?php
class ScoopItBridge extends BridgeAbstract{

	public $maintainer = "Pitchoule";
	public $name = "ScoopIt";
	public $uri = "http://www.scoop.it";
	public $description = "Returns most recent results from ScoopIt.";

    public $parameters = array( array(
        'u'=>array(
            'name'=>'keyword',
            'required'=>true
        )
    ));

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
        $html = '';
        if ($param['u']['value'] != '') {
            $this->request = $param['u']['value'];
            $link = 'http://scoop.it/search?q=' .urlencode($this->request);

            $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request ScoopIt. for : ' . $link);

            foreach($html->find('div.post-view') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_title',0)->plaintext);
                $item['content'] = preg_replace('~[[:cntrl:]]~', '', $element->find('div.tCustomization_post_description', 0)->plaintext);
                $this->items[] = $item;
            }
        } else {
            $this->returnServerError('You must specify a keyword');
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

