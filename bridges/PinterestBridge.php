<?php
/**
 * RssBridgePinterest
 * Returns the newest photos on a board
 *
 * @name Pinterest Bridge
 * @description Returns the newest images on a board
 * @use1(u="username",b="board")
 * @use2(q="keyword")
 */
class PinterestBridge extends BridgeAbstract{
    
    private $username;
    private $board;
    private $query;
    
    public function collectData(array $param){
        $html = '';
        if (isset($param['u']) || isset($param['b'])) {
        
            if (empty($param['u']))
            {
                $this->returnError('You must specify a Pinterest username (?u=...).', 400);
            }

            if (empty($param['b']))
            {
                $this->returnError('You must specify a Pinterest board for this username (?b=...).', 400);
            }
            
            $this->username = $param['u'];
            $this->board = $param['b'];
            $html = file_get_html($this->getURI().'/'.urlencode($this->username).'/'.urlencode($this->board)) or $this->returnError('Could not request Pinterest.', 404);
        } else if (isset($param['q']))
        {
        	$this->query = $param['q'];
        	$html = file_get_html($this->getURI().'/search/?q='.urlencode($this->query)) or $this->returnError('Could not request Pinterest.', 404);
        }
        
        else {
            $this->returnError('You must specify a Pinterest username and a board name (?u=...&b=...).', 400);
        }
       
        
        foreach($html->find('div.pinWrapper') as $div)
        {
        	$a = $div->find('a.pinImageWrapper',0);
        	
        	$img = $a->find('img', 0);
        	
        	$item = new \Item();
        	$item->uri = $this->getURI().$a->getAttribute('href');
        	$item->content = '<img src="' . htmlentities($img->getAttribute('src')) . '" alt="" />';
        	
        	
        	if (isset($this->query))
        	{
        		$avatar = $div->find('img.creditImg', 0);
        		$username = $div->find('span.creditName', 0);
        		$board = $div->find('span.creditTitle', 0);
        		
        		$item->username =$username->innertext;	
        		$item->fullname = $board->innertext;
        		$item->avatar = $avatar->getAttribute('src');
        		
        		$item->content .= '<br /><img align="left" style="margin: 2px 4px;" src="'.htmlentities($item->avatar).'" /> <strong>'.$item->username.'</strong>';
        		$item->content .= '<br />'.$item->fullname;
        	} else {
        	
        		$credit = $div->find('a.creditItem',0);
        		$item->content .= '<br />'.$credit->innertext;
        	}
        	
        	$item->title = basename($img->getAttribute('alt'));
        	
        	//$item->timestamp = $media->created_time;
        	$this->items[] = $item;
        	
        }
    }

    public function getName(){
    	
    	if (isset($this->query))
    	{
    		return $this->query .' - Pinterest';
    	} else {
        	return $this->username .' - '. $this->board.' - Pinterest';
    	}
    }

    public function getURI(){
        return 'http://www.pinterest.com';
    }

    public function getCacheDuration(){
        return 0; 
    }
}
