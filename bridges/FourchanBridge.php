<?php
class FourchanBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "4chan";
	const URI = "https://boards.4chan.org/";
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = "Returns posts from the specified thread";

    const PARAMETERS = array( array(
          'c'=>array(
            'name'=>'Thread category',
            'required'=>true
          ),
          't'=>array(
            'name'=>'Thread number',
            'type'=>'number',
            'required'=>true
          )
    ));

    public function getURI(){
      return static::URI.$this->getInput('c').'/thread/'.$this->getInput('t');

    }

  public function collectData(){

    $html = getSimpleHTMLDOM($this->getURI())
      or returnServerError("Could not request 4chan, thread not found");

	foreach($html->find('div.postContainer') as $element) {
		$item = array();
		$item['id'] = $element->find('.post', 0)->getAttribute('id');
		$item['uri'] = $this->getURI().'#'.$item['id'];
		$item['timestamp'] = $element->find('span.dateTime', 0)->getAttribute('data-utc');
		$item['author'] = $element->find('span.name', 0)->plaintext;

        $file=$element->find('.file', 0);
		if(!empty($file) ) {
			$item['image'] = $element->find('.file a', 0)->href;
			$item['imageThumb'] = $element->find('.file img', 0)->src;
			if(!isset($item['imageThumb']) and strpos($item['image'], '.swf') !== FALSE)
				$item['imageThumb'] = 'http://i.imgur.com/eO0cxf9.jpg';
		}
		if(!empty($element->find('span.subject', 0)->innertext )) {
			$item['subject'] = $element->find('span.subject', 0)->innertext;
		}

		$item['title'] = 'reply '.$item['id'].' | '.$item['author'];
        if(isset($item['subject'])){
          $item['title'] = $item['subject'].' - '.$item['title'];
        }

        $content = $element->find('.postMessage', 0)->innertext;
        $content = str_replace('href="#p','href="'.$this->getURI().'#p',$content);
		$item['content'] = '<span id="'.$item['id'].'">'.$content.'</span>';
        if(isset($item['image'])){
          $item['content'] = '<a href="'.$item['image'].'">'
            .'<img alt="'.$item['id'].'" src="'.$item['imageThumb'].'" />'
            .'</a><br>'
            .$item['content'];
        }
		$this->items[] = $item;
	}
	$this->items = array_reverse($this->items);
  }
}
