<?php
class WhydBridge extends BridgeAbstract{

	const MAINTAINER = "kranack";
	const NAME = "Whyd Bridge";
	const URI = "http://www.whyd.com/";
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = "Returns 10 newest music from user profile";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'username/id',
            'required'=>true
        )
    ));

    private $userName='';

	public function collectData(){
		$html = '';
        if (strlen(preg_replace("/[^0-9a-f]/",'', $this->getInput('u'))) == 24){
            // is input the userid ?
            $html = getSimpleHTMLDOM(
                self::URI.'u/'.preg_replace("/[^0-9a-f]/",'', $this->getInput('u'))
            ) or returnServerError('No results for this query.');
        } else { // input may be the username
            $html = getSimpleHTMLDOM(
                self::URI.'search?q='.urlencode($this->getInput('u'))
            ) or returnServerError('No results for this query.');

            for ($j = 0; $j < 5; $j++) {
                if (strtolower($html->find('div.user', $j)->find('a',0)->plaintext) == strtolower($this->getInput('u'))) {
                    $html = getSimpleHTMLDOM(
                        self::URI . $html->find('div.user', $j)->find('a', 0)->getAttribute('href')
                    ) or returnServerError('No results for this query');
                    break;
                }
            }
        }
        $this->userName = $html->find('div#profileTop', 0)->find('h1', 0)->plaintext;

		for($i = 0; $i < 10; $i++) {
			$track = $html->find('div.post', $i);
            $item = array();
            $item['author'] = $track->find('h2', 0)->plaintext;
            $item['title'] = $track->find('h2', 0)->plaintext;
            $item['content'] = $track->find('a.thumb',0) . '<br/>' . $track->find('h2', 0)->plaintext;
            $item['id'] = self::URI . $track->find('a.no-ajaxy',0)->getAttribute('href');
            $item['uri'] = self::URI . $track->find('a.no-ajaxy',0)->getAttribute('href');
            $this->items[] = $item;
        }
    }
	public function getName(){
		return (!empty($this->userName) ? $this->userName .' - ' : '') .'Whyd Bridge';
	}
}


