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
	$page = 0;$tags='';
        if (isset($this->getInput('p'))) {
            $page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
        }
        if (isset($this->getInput('t'))) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://dollbooru.org/post/list/$tags/$page") or $this->returnServerError('Could not request Dollbooru.');


	foreach($html->find('div[class=shm-image-list] a') as $element) {
		$item = array();
		$item['uri'] = 'http://dollbooru.org'.$element->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('data-post-id'));
		$item['timestamp'] = time();
		$thumbnailUri = 'http://dollbooru.org'.$element->find('img', 0)->src;
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
