<?php
/**
* WhydBridge
* Returns the newest music from user
*
* @name Whyd Bridge
* @homepage http://www.whyd.com/
* @description Returns 10 newest music from user profile 
* @maintainer kranack
* @update 2014-07-18
* @use1(u="username")
*
*/
class WhydBridge extends BridgeAbstract{
    
	private $request;
	private $name;
    
	public function collectData(array $param){
		$html = '';
		if (isset($param['u']))
		{
			$this->request = $param['u'];
            if (strlen(preg_replace("/[^0-9a-f]/",'', $this->request)) == 24) { // is input the userid ?
				$html = file_get_html('http://www.whyd.com/u/'.preg_replace("/[^0-9a-f]/",'', $this->request)) or $this->returnError('No results for this query.', 404);
			} else { // input may be the username
				$html = file_get_html('http://www.whyd.com/search?q='.urlencode($this->request)) or $this->returnError('No results for this query.', 404);
				for ($j = 0; $j < 5; $j++) {
					if (strtolower($html->find('div.user', $j)->find('a',0)->plaintext) == strtolower($this->request)) {
						$html = file_get_html('http://www.whyd.com' . $html->find('div.user', $j)->find('a', 0)->getAttribute('href')) or $this->returnError('No results for this query', 404);
						break;
					}
				}
			}
            $this->name = $html->find('div#profileTop', 0)->find('h1', 0)->plaintext;
		} 
		else
		{
			$this->returnError('You must specify username', 400);
		}

		for($i = 0; $i < 10; $i++) {
			$track = $html->find('div.post', $i);
            $item = new \Item();
            $item->name = $track->find('h2', 0)->plaintext;
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

	public function getURI(){
		return 'http://www.whyd.com/';
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}
