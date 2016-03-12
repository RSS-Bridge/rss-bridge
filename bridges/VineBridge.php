<?php
class VineBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "ckiw";
		$this->name = "Vine bridge";
		$this->uri = "http://vine.co/";
		$this->description = "Returns the latests vines from vine user page";
		$this->update = "2016-03-12";

		$this->parameters[] =
		'[
			{
				"name" : "User id",
				"identifier" : "u",
				"type" : "text",
				"required" : "true"
			}
		]';
	}

	public function collectData(array $param){
    $html = '';
    $uri = 'http://vine.co/u/'.$param['u'].'?mode=list';

    $html = file_get_html($uri) or $this->returnError('No results for this query.', 404);

		foreach($html->find('.post') as $element) {
			$a = $element->find('a', 0);
			$a->href = str_replace('https://', 'http://', $a->href);
			$time = strtotime(ltrim($element->find('p', 0)->plaintext, " Uploaded at "));
			$video = $element->find('video', 0);
			$video->controls = "true";
			$element->find('h2', 0)->outertext = '';

			$item = new \Item();
			$item->uri = $a->href;
			$item->timestamp = $time;
			$item->title = $a->plaintext;
			$item->content = $element;

			$this->items[] = $item;
		}

    }

    public function getName(){
        return 'Vine';
    }

    public function getURI(){
        return 'http://vine.co';
    }

    public function getCacheDuration(){
        return 10; //seconds
    }
}
