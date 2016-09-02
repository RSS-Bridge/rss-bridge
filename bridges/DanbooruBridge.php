<?php
class DanbooruBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Danbooru";
	const URI = "http://donmai.us/";
	const DESCRIPTION = "Returns images from given page";

    const PARAMETERS = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
        $page = $this->getInput('p')?$this->getInput('p'):1;
        $tags = urlencode($this->getInput('t'));

        $html = $this->getSimpleHTMLDOM(self::URI."posts?&page=$page&tags=$tags")
            or $this->returnServerError('Could not request Danbooru.');
	foreach($html->find('div[id=posts] article') as $element) {
		$item = array();
		$item['uri'] = self::URI.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('data-id'));
		$item['timestamp'] = time();
		$thumbnailUri = self::URI.$element->find('img', 0)->src;
		$item['tags'] = $element->find('img', 0)->getAttribute('alt');
		$item['title'] = 'Danbooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
