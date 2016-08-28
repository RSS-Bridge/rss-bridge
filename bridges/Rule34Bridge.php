<?php
class Rule34Bridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Rule34";
	public $uri = "http://rule34.xxx/";
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
        if (isset($this->getInput('p'))) {
		$page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
		$page = $page - 1;
		$page = $page * 50;
        }
        if (isset($this->getInput('t'))) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://rule34.xxx/index.php?page=post&s=list&tags=$tags&pid=$page") or $this->returnServerError('Could not request Rule34.');


	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = 'http://rule34.xxx/'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('id'));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->find('img', 0)->getAttribute('alt');
		$item['title'] = 'Rule34 | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
