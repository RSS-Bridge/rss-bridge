<?php
class WhydBridge extends BridgeAbstract{

	private $request;
	public $name;

    public function loadMetadatas() {

		$this->maintainer = "kranack";
		$this->name = "Whyd Bridge";
		$this->uri = "http://www.whyd.com/";
		$this->description = "Returns 10 newest music from user profile";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'username/id',
            'required'=>true
          )
        );

	}

	public function collectData(array $param){
		$html = '';
		if (isset($param['u']))
		{
			$this->request = $param['u'];
            if (strlen(preg_replace("/[^0-9a-f]/",'', $this->request)) == 24) { // is input the userid ?
				$html = $this->getSimpleHTMLDOM('http://www.whyd.com/u/'.preg_replace("/[^0-9a-f]/",'', $this->request)) or $this->returnServerError('No results for this query.');
			} else { // input may be the username
				$html = $this->getSimpleHTMLDOM('http://www.whyd.com/search?q='.urlencode($this->request)) or $this->returnServerError('No results for this query.');
				for ($j = 0; $j < 5; $j++) {
					if (strtolower($html->find('div.user', $j)->find('a',0)->plaintext) == strtolower($this->request)) {
						$html = $this->getSimpleHTMLDOM('http://www.whyd.com' . $html->find('div.user', $j)->find('a', 0)->getAttribute('href')) or $this->returnServerError('No results for this query');
						break;
					}
				}
			}
            $this->name = $html->find('div#profileTop', 0)->find('h1', 0)->plaintext;
		}
		else
		{
			$this->returnClientError('You must specify username');
		}

		for($i = 0; $i < 10; $i++) {
			$track = $html->find('div.post', $i);
            $item = new \Item();
            $item->author = $track->find('h2', 0)->plaintext;
            $item->title = $track->find('h2', 0)->plaintext;
            $item->content = $track->find('a.thumb',0) . '<br/>' . $track->find('h2', 0)->plaintext;
            $item->id = 'http://www.whyd.com' . $track->find('a.no-ajaxy',0)->getAttribute('href');
            $item->uri = 'http://www.whyd.com' . $track->find('a.no-ajaxy',0)->getAttribute('href');
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


