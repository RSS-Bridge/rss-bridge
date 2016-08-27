<?php
class PinterestBridge extends BridgeAbstract{

    private $username;
    private $board;
    private $query;

	public $maintainer = "pauder";
	public $name = "Pinterest Bridge";
	public $uri = "http://www.pinterest.com";
	public $description = "Returns the newest images on a board";

    public $parameters = array(
        'By username and board' => array(
            'u'=>array('name'=>'username'),
            'b'=>array('name'=>'board')
        ),
        'From search' => array(
            'q'=>array('name'=>'Keyword')
        )
    );

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
        $html = '';
        if (isset($param['u']['value']) || isset($param['b']['value'])) {

            if (empty($param['u']['value']))
            {
                $this->returnClientError('You must specify a Pinterest username (?u=...).');
            }

            if (empty($param['b']['value']))
            {
                $this->returnClientError('You must specify a Pinterest board for this username (?b=...).');
            }

            $this->username = $param['u']['value'];
            $this->board = $param['b']['value'];
            $html = $this->getSimpleHTMLDOM($this->getURI().'/'.urlencode($this->username).'/'.urlencode($this->board)) or $this->returnServerError('Username and/or board not found');

        } else if (isset($param['q']['value']))
        {
        	$this->query = $param['q']['value'];
        	$html = $this->getSimpleHTMLDOM($this->getURI().'/search/?q='.urlencode($this->query)) or $this->returnServerError('Could not request Pinterest.');
        }

        else {
            $this->returnClientError('You must specify a Pinterest username and a board name (?u=...&b=...).');
        }


        foreach($html->find('div.pinWrapper') as $div)
        {
        	$a = $div->find('a.pinImageWrapper',0);

        	$img = $a->find('img', 0);

        	$item = array();
        	$item['uri'] = $this->getURI().$a->getAttribute('href');
        	$item['content'] = '<img src="' . htmlentities(str_replace('/236x/', '/736x/', $img->getAttribute('src'))) . '" alt="" />';


        	if (isset($this->query))
        	{
        		$avatar = $div->find('div.creditImg', 0)->find('img', 0);
				$avatar = $avatar->getAttribute('data-src');
				$avatar = str_replace("\\", "", $avatar);


        		$username = $div->find('div.creditName', 0);
        		$board = $div->find('div.creditTitle', 0);

        		$item['username'] =$username->innertext;
        		$item['fullname'] = $board->innertext;
        		$item['avatar'] = $avatar;

        		$item['content'] .= '<br /><img align="left" style="margin: 2px 4px;" src="'.htmlentities($item['avatar']).'" /> <strong>'.$item['username'].'</strong>';
        		$item['content'] .= '<br />'.$item['fullname'];
        	}

        	$item['title'] = $img->getAttribute('alt');

        	//$item['timestamp'] = $media->created_time;
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
}
