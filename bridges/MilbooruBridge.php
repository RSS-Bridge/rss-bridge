<?php
class MilbooruBridge extends BridgeAbstract{


	public $maintainer = "mitsukarenai";
	public $name = "Milbooru";
	public $uri = "http://sheslostcontrol.net/moe/shimmie/";
	public $description = "Returns images from given page";

    public $parameters = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
	$page = 0;$tags='';
        if ($this->getInput('p')) {
            $page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
        }
        if ($this->getInput('t')) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://sheslostcontrol.net/moe/shimmie/index.php?q=/post/list/$tags/$page") or $this->returnServerError('Could not request Milbooru.');


	foreach($html->find('div[class=shm-image-list] span[class=thumb]') as $element) {
		$item = array();
		$item['uri'] = 'http://sheslostcontrol.net/moe/shimmie/'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->find('a', 0)->getAttribute('data-post-id'));
		$item['timestamp'] = time();
		$thumbnailUri = 'http://sheslostcontrol.net/moe/shimmie/'.$element->find('img', 0)->src;
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
