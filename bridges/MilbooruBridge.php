<?php
class MilbooruBridge extends BridgeAbstract{


	const MAINTAINER = "mitsukarenai";
	const NAME = "Milbooru";
	const URI = "http://sheslostcontrol.net/moe/shimmie/";
	const DESCRIPTION = "Returns images from given page";

    const PARAMETERS = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
        $html = $this->getSimpleHTMLDOM(
            self::URI.'?q=/post/list/'.urlencode($this->getInput('t')).'/'.$this->getInput('p')
        )or $this->returnServerError('Could not request Milbooru.');

	foreach($html->find('div[class=shm-image-list] span[class=thumb]') as $element) {
		$item = array();
		$item['uri'] = self::URI.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->find('a', 0)->getAttribute('data-post-id'));
		$item['timestamp'] = time();
		$thumbnailUri = self::URI.$element->find('img', 0)->src;
		$item['tags'] = $element->find('a', 0)->getAttribute('data-tags');
		$item['title'] = 'Milbooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
