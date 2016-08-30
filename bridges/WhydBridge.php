<?php
class WhydBridge extends BridgeAbstract{

	public $maintainer = "kranack";
	public $name = "Whyd Bridge";
	public $uri = "http://www.whyd.com/";
	public $description = "Returns 10 newest music from user profile";

    public $parameters = array( array(
        'u'=>array(
            'name'=>'username/id',
            'required'=>true
        )
    ));

	public function collectData(){
		$html = '';
        if (strlen(preg_replace("/[^0-9a-f]/",'', $this->getInput('u'))) == 24){
            // is input the userid ?
            $html = $this->getSimpleHTMLDOM(
                $this->uri.'u/'.preg_replace("/[^0-9a-f]/",'', $this->getInput('u'))
            ) or $this->returnServerError('No results for this query.');
        } else { // input may be the username
            $html = $this->getSimpleHTMLDOM(
                $this->uri.'search?q='.urlencode($this->getInput('u'))
            ) or $this->returnServerError('No results for this query.');

            for ($j = 0; $j < 5; $j++) {
                if (strtolower($html->find('div.user', $j)->find('a',0)->plaintext) == strtolower($this->getInput('u'))) {
                    $html = $this->getSimpleHTMLDOM(
                        $this->uri . $html->find('div.user', $j)->find('a', 0)->getAttribute('href')
                    ) or $this->returnServerError('No results for this query');
                    break;
                }
            }
        }
        $this->name = $html->find('div#profileTop', 0)->find('h1', 0)->plaintext;

		for($i = 0; $i < 10; $i++) {
			$track = $html->find('div.post', $i);
            $item = array();
            $item['author'] = $track->find('h2', 0)->plaintext;
            $item['title'] = $track->find('h2', 0)->plaintext;
            $item['content'] = $track->find('a.thumb',0) . '<br/>' . $track->find('h2', 0)->plaintext;
            $item['id'] = 'http://www.whyd.com' . $track->find('a.no-ajaxy',0)->getAttribute('href');
            $item['uri'] = 'http://www.whyd.com' . $track->find('a.no-ajaxy',0)->getAttribute('href');
            $this->items[] = $item;
        }
    }
	public function getName(){
		return (!empty($this->name) ? $this->name .' - ' : '') .'Whyd Bridge';
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}


