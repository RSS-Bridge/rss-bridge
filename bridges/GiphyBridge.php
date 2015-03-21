<?php
/**
* RssBridgeGiphy
* Based on https://github.com/mitsukarenai/twitterbridge-noapi
* 2014-12-05
*
* @name Giphy Bridge
* @homepage http://giphy.com/
* @description Bridge for giphy.com
* @maintainer kraoc
* @use1(s="search tag")
* @use2(n="max number of returned items")
*/

define(GIPHY_LIMIT, 10);

class GiphyBridge extends BridgeAbstract{

	public function collectData(array $param){
		$html = ''; 
        $base_url = 'http://giphy.com';
		if (isset($param['s'])) {   /* keyword search mode */
			$html = file_get_html($base_url.'/search/'.urlencode($param['s'].'/')) or $this->returnError('No results for this query.', 404);
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
                
                $html2 = file_get_html($base_url . $href) or $this->returnError('No results for this query.', 404);                                
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

	public function getName(){
		return 'Giphy Bridge';
	}

	public function getURI(){
		return 'http://giphy.com/';
	}

	public function getCacheDuration(){
		return 300; // 5 minutes
	}
    
	public function getUsername(){
		return $this->items[0]->username;
	}
}
