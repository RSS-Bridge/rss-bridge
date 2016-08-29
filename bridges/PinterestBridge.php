<?php
class PinterestBridge extends BridgeAbstract{

	public $maintainer = "pauder";
	public $name = "Pinterest Bridge";
	public $uri = "http://www.pinterest.com/";
	public $description = "Returns the newest images on a board";

    public $parameters = array(
        'By username and board' => array(
          'u'=>array(
            'name'=>'username',
            'required'=>true
          ),
          'b'=>array(
            'name'=>'board',
            'required'=>true
          )
        ),
        'From search' => array(
          'q'=>array(
            'name'=>'Keyword',
            'required'=>true
          )
        )
    );


    public function collectData(){
      $html = $this->getSimpleHTMLDOM($this->getURI()) ;
      if(!$html){
        switch($this->queriedContext){
        case 'By username and board':
          $this->returnServerError('Username and/or board not found');
        case 'From search':
          $this->returnServerError('Could not request Pinterest.');
        }
      }

        foreach($html->find('div.pinWrapper') as $div)
        {
        	$a = $div->find('a.pinImageWrapper',0);

        	$img = $a->find('img', 0);

        	$item = array();
        	$item['uri'] = $this->getURI().$a->getAttribute('href');
        	$item['content'] = '<img src="' . htmlentities(str_replace('/236x/', '/736x/', $img->getAttribute('src'))) . '" alt="" />';


        	if ($this->queriedContext==='From search')
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

    public function getURI(){
      switch($this->queriedContext){
      case 'By username and board':
        $uri = $this->uri.urlencode($this->getInput('u')).'/'.urlencode($this->getInput('b'));
        break;
      case 'From search':
        $uri = $this->uri.'search/?q='.urlencode($this->getInput('q'));
        break;
      }

      return $uri;
    }

    public function getName(){
      switch($this->queriedContext){
      case 'By username and board':
            $specific = $this->getInput('u').'-'.$this->getInput('b');
        break;
      case 'From search':
            $specific = $this->getInput('q');
        break;
      }
      return $specific .' - '.$this->name;
    }
}
