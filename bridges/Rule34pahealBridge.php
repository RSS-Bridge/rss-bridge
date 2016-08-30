<?php
class Rule34pahealBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Rule34paheal";
	const URI = "http://rule34.paheal.net/";
	const DESCRIPTION = "Returns images from given page";

    const PARAMETERS = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));


    public function collectData(){
      $html = $this->getSimpleHTMLDOM(self::URI.'post/list/'.$tags.'/'.$page)
        or $this->returnServerError('Could not request Rule34paheal.');


	foreach($html->find('div[class=shm-image-list] div[class=shm-thumb]') as $element) {
		$item = array();
		$item['uri'] = self::URI.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->find('img', 0)->getAttribute('id'));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->getAttribute('data-tags');
		$item['title'] = 'Rule34paheal | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
