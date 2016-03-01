<?php
class PinterestBridge extends BridgeAbstract{

    private $username;
    private $board;
    private $query;

    public function loadMetadatas() {

		$this->maintainer = "pauder";
		$this->name = "Pinterest Bridge";
		$this->uri = "http://www.pinterest.com/";
		$this->description = "Returns the newest images on a board";
		$this->update = "2014-05-25";

		$this->parameters["By username and board"] =
		'[
			{
				"name" : "username",
				"identifier" : "u"
			},
			{
				"name" : "board",
				"identifier" : "b"

			}
		]';

		$this->parameters["From search"] =
		'[
			{
				"name" : "Keyword",
				"identifier" : "q"
			}
		]';
	}

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
            $html = file_get_html($this->getURI().'/'.urlencode($this->username).'/'.urlencode($this->board)) or $this->returnError('Username and/or board not found', 404);

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
        	$item->content = '<img src="' . htmlentities(str_replace('/236x/', '/736x/', $img->getAttribute('src'))) . '" alt="" />';
        	
        	
        	if (isset($this->query))
        	{
        		$avatar = $div->find('div.creditImg', 0)->find('img', 0);
				$avatar = $avatar->getAttribute('data-src');
				$avatar = str_replace("\\", "", $avatar);


        		$username = $div->find('div.creditName', 0);
        		$board = $div->find('div.creditTitle', 0);
        		
        		$item->username =$username->innertext;	
        		$item->fullname = $board->innertext;
        		$item->avatar = $avatar;
        		
        		$item->content .= '<br /><img align="left" style="margin: 2px 4px;" src="'.htmlentities($item->avatar).'" /> <strong>'.$item->username.'</strong>';
        		$item->content .= '<br />'.$item->fullname;
        	}
        	
        	$item->title = $img->getAttribute('alt');
        	
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
        return 3600; 
    }
}
