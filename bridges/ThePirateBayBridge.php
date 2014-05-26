<?php
/**
* RssBridgeThePirateBay
* Returns the newest interesting images from http://www.flickr.com/explore
* 2014-05-25
*
* @name The Pirate Bay
* @homepage https://thepiratebay.se/
* @description Returns results for the keywords
* @maintainer mitsukarenai
* @update 2014-05-26
* @use1(q="keywords")
*/
class ThePirateBayBridge extends BridgeAbstract{

	public function collectData(array $param){

		if (!isset($param['q']))
			$this->returnError('You must specify a keyword (?q=...)', 400);

        	$html = file_get_html('https://thepiratebay.se/search/'.rawurlencode($param['q']).'/0/99/0') or $this->returnError('Could not request TPB.', 404);

		if($html->find('table#searchResult', 0) == FALSE)
			$this->returnError('No result for this query', 404);

		foreach($html->find('tr') as $element) {
			$item = new \Item();
			$item->uri = 'https://thepiratebay.se/'.$element->find('a.detLink',0)->href;
			$item->id = $item->uri;
			$item->timestamp = time();
			$item->title = $element->find('a.detLink',0)->plaintext;
			$item->content = $element->find('font',0)->plaintext.'<br><a href="'.$element->find('a',3)->href.'">download</a>';
			if(!empty($item->title))
				$this->items[] = $item;
		}
	}

    public function getName(){
        return 'The Pirate Bay';
    }

    public function getURI(){
        return 'https://thepiratebay.se/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
