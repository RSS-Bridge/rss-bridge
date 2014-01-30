<?php
/**
 * RssBridgePinterest
 * Returns the newest photos on a board
 *
 * @name Pinterest Bridge
 * @description Returns the newest images on a board
 * @use1(u="username",b="board")
 */
class PinterestBridge extends BridgeAbstract{
    
    private $username;
    private $board;
    
    public function collectData(array $param){
        $html = '';
        if (isset($param['u']) && isset($param['b'])) {
            $this->username = $param['u'];
            $this->board = $param['b'];
            $html = file_get_html($this->getURI().'/'.urlencode($this->username).'/'.urlencode($this->board)) or $this->returnError('Could not request Pinterest.', 404);
        }
        else {
            $this->returnError('You must specify a Pinterest username and a board name (?u=...&b=...).', 400);
        }
        
        $innertext = null;
        
        foreach($html->find('div.pinWrapper') as $div)
        {
        	$a = $div->find('a.pinImageWrapper',0);
        	
        	$img = $a->find('img', 0);
        	
        	$item = new \Item();
        	$item->uri = $this->getURI().$a->getAttribute('href');
        	$item->content = '<img src="' . htmlentities($img->getAttribute('src')) . '" alt="" />';
        	
        	$credit = $div->find('a.creditItem',0);
        	
        	$item->content .= '<br />'.$credit->innertext;
        	
        	$item->title = basename($img->getAttribute('alt'));
        	
        	//$item->timestamp = $media->created_time;
        	$this->items[] = $item;
        	
        }
    }

    public function getName(){
        return $this->username .' - '. $this->board;
    }

    public function getURI(){
        return 'http://www.pinterest.com';
    }

    public function getCacheDuration(){
        return 0; 
    }
}
