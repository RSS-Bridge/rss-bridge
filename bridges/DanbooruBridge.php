<?php
class DanbooruBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Danbooru";
	public $uri = "http://donmai.us/";
	public $description = "Returns images from given page";

    public $parameters = array( array(
        'p'=>array('name'=>'page'),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
	$page = 1;$tags='';
        if (isset($this->getInput('p'))) {
            $page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
        }
        if (isset($this->getInput('t'))) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://donmai.us/posts?&page=$page&tags=$tags") or $this->returnServerError('Could not request Danbooru.');
	foreach($html->find('div[id=posts] article') as $element) {
		$item = array();
		$item['uri'] = 'http://donmai.us'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('data-id'));
		$item['timestamp'] = time();
		$thumbnailUri = 'http://donmai.us'.$element->find('img', 0)->src;
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
