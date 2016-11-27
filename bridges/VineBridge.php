<?php
class VineBridge extends BridgeAbstract {

	const MAINTAINER = "ckiw";
	const NAME = "Vine bridge";
	const URI = "http://vine.co/";
	const DESCRIPTION = "Returns the latests vines from vine user page";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'User id',
            'required'=>true
        )
    ));

	public function collectData(){
    $html = '';
    $uri = self::URI.'/u/'.$this->getInput('u').'?mode=list';

    $html = getSimpleHTMLDOM($uri)
      or returnServerError('No results for this query.');

		foreach($html->find('.post') as $element) {
			$a = $element->find('a', 0);
			$a->href = str_replace('https://', 'http://', $a->href);
			$time = strtotime(ltrim($element->find('p', 0)->plaintext, " Uploaded at "));
			$video = $element->find('video', 0);
			$video->controls = "true";
			$element->find('h2', 0)->outertext = '';

			$item = array();
			$item['uri'] = $a->href;
			$item['timestamp'] = $time;
			$item['title'] = $a->plaintext;
			$item['content'] = $element;

			$this->items[] = $item;
		}

    }
}
