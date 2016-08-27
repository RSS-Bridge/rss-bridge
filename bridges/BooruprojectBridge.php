<?php
class BooruprojectBridge extends BridgeAbstract{


	public $maintainer = "mitsukarenai";
	public $name = "Booruproject";
	public $uri = "http://booru.org/";
	public $description = "Returns images from given page and booruproject instance (****.booru.org)";

    public $parameters = array( array(
          'i'=>array(
            'name'=>'instance (required)',
            'required'=>true
          ),
          'p'=>array('name'=>'page'),
          't'=>array('name'=>'tags')
        ));

    public function collectData(){
	$page = 0; $tags = '';
        if (!empty($this->getInput('p'))) {
		$page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
		$page = $page - 1;
		$page = $page * 20;
        }
        if (!empty($this->getInput('t'))) {
            $tags = '&tags='.urlencode($this->getInput('t'));
        }
	if (empty($this->getInput('i'))) {
		$this->returnServerError('Please enter a ***.booru.org instance.');
	}
        $html = $this->getSimpleHTMLDOM("http://".$this->getInput('i').".booru.org/index.php?page=post&s=list&pid=".$page.$tags) or $this->returnServerError('Could not request Booruproject.');


	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = 'http://'.$this->getInput('i').'.booru.org/'.$element->find('a', 0)->href;
		$item['postid'] = (int)preg_replace("/[^0-9]/",'', $element->find('a', 0)->getAttribute('id'));
		$item['timestamp'] = time();
		$item['tags'] = $element->find('img', 0)->getAttribute('title');
		$item['title'] = 'Booruproject '.$this->getInput('i').' | '.$item['postid'];
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $element->find('img', 0)->src . '" /></a><br>Tags: '.$item['tags'];
		$this->items[] = $item;
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
