<?php
class SafebooruBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Safebooru";
	public $uri = "http://safebooru.org/";
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
		$page = $page - 1;
		$page = $page * 40;
        }
        if ($this->getInput('t')) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://safebooru.org/index.php?page=post&s=list&tags=$tags&pid=$page") or $this->returnServerError('Could not request Safebooru.');


	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = 'http://safebooru.org/'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('id'));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->find('img', 0)->getAttribute('alt');
		$item['title'] = 'Safebooru | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
