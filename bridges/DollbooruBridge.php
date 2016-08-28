<?php
class DollbooruBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Dollbooru";
	public $uri = "http://dollbooru.org/";
	public $description = "Returns images from given page";


    public $parameters  = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
        $page=$this->getInput('p');
        $tags = urlencode($this->getInput('t'));
        $html = $this->getSimpleHTMLDOM($this->uri."post/list/$tags/$page")
            or $this->returnServerError('Could not request Dollbooru.');


	foreach($html->find('div[class=shm-image-list] a') as $element) {
		$item = array();
		$item['uri'] = $this->uri.$element->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('data-post-id'));
		$item['timestamp'] = time();
		$thumbnailUri = $this->uri.$element->find('img', 0)->src;
		$item['tags'] = $element->getAttribute('data-tags');
		$item['title'] = 'Dollbooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
