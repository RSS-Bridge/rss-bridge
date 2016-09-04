<?php
class FourchanBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "4chan";
	const URI = "https://boards.4chan.org/";
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

    $html = $this->getSimpleHTMLDOM($this->getURI())
      or $this->returnServerError("Could not request 4chan, thread not found");

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
		$item['title'] = (isset($item['subject']) ? $item['subject'].' - ' : '' ) . 'reply '.$item['id'].' | '.$item['author'];


		$item['content'] = (isset($item['image']) ? '<a href="'.$item['image'].'"><img alt="'.$item['id'].'" src="'.$item['imageThumb'].'" /></a><br>' : '') . '<span id="'.$item['id'].'">'.$element->find('.postMessage', 0)->innertext.'</span>';
		$this->items[] = $item;
	}
	$this->items = array_reverse($this->items);
  }

    public function getCacheDuration(){
        return 300; // 5min
    }
}
