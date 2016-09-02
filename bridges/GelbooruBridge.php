<?php
class GelbooruBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Gelbooru";
	const URI = "http://gelbooru.com/";
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
            self::URI.'index.php?page=post&s=list&'
            .'&pid='.($this->getInput('p')?($this->getInput('p') -1)*63:'')
            .'&tags='.urlencode($this->getInput('t'))
        ) or $this->returnServerError('Could not request Gelbooru.');

	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = self::URI.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('id'));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->find('img', 0)->getAttribute('alt');
		$item['title'] = 'Gelbooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
