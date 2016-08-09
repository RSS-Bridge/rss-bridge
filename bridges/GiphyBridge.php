<?php
define('GIPHY_LIMIT', 10);

class GiphyBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "kraoc";
		$this->name = "Giphy Bridge";
		$this->uri = "http://giphy.com/";
		$this->description = "Bridge for giphy.com";
		$this->update = "2016-08-09";

		$this->parameters["By tag"] =
		'[
			{
				"name" : "search tag",
				"identifier" : "s"
			}
		]';

		$this->parameters["Without tag"] =
		'[
			{
				"name" : "max number of returned items",
				"type" : "number",
				"identifier" : "n"
			}
		]';
	}

	public function collectData(array $param){
		$html = ''; 
        $base_url = 'http://giphy.com';
		if (isset($param['s'])) {   /* keyword search mode */
			$html = $this->file_get_html($base_url.'/search/'.urlencode($param['s'].'/')) or $this->returnError('No results for this query.', 404);
		}
		else {
			$this->returnError('You must specify a search worf (?s=...).', 400);
		}

        $max = GIPHY_LIMIT;
        if (isset($param['n'])) {
            $max = (integer) $param['n'];
        }
        
        $limit = 0;
        $kw = urlencode($param['s']);
        foreach($html->find('div.hoverable-gif') as $entry) {
            if($limit < $max) {
                $node = $entry->first_child();                                
                $href = $node->getAttribute('href');                
                
                $html2 = $this->file_get_html($base_url . $href) or $this->returnError('No results for this query.', 404);                                
                $figure = $html2->getElementByTagName('figure');
                $img = $figure->firstChild();
                $caption = $figure->lastChild();
                
                $item = new \Item();
                $item->id = $img->getAttribute('data-gif_id');
                $item->uri = $img->getAttribute('data-bitly_gif_url');	
                $item->username = 'Giphy - '.ucfirst($kw);
                $title = $caption->innertext();
                    $title = preg_replace('/\s+/', ' ',$title);
                    $title = str_replace('animated GIF', '', $title);
                    $title = str_replace($kw, '', $title);
                    $title = preg_replace('/\s+/', ' ',$title);
                    $title = trim($title);
                    if (strlen($title) <= 0) {
                        $title = $item->id;
                    }
                $item->title = trim($title);
                $item->content =
                    '<a href="'.$item->uri.'">'
                        .'<img src="'.$img->getAttribute('src').'" width="'.$img->getAttribute('data-original-width').'" height="'.$img->getAttribute('data-original-height').'" />'
                    .'</a>';
                
                $this->items[] = $item;                
                $limit++;
            }
        }
	}

	public function getCacheDuration(){
		return 300; // 5 minutes
	}
}
