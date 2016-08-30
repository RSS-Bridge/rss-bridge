<?php
class BooruprojectBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Booruproject";
	const URI = "http://booru.org/";
	const DESCRIPTION = "Returns images from given page and booruproject instance (****.booru.org)";

    const PARAMETERS = array( array(
          'i'=>array(
            'name'=>'instance (required)',
            'required'=>true
          ),
          'p'=>array(
              'name'=>'page',
              'type'=>'number'
          ),
          't'=>array('name'=>'tags')
        ));

    function getURI(){
        return 'http://'.$this->getInput('i').'.booru.org/';
    }

    public function collectData(){
        $html = $this->getSimpleHTMLDOM(
            $this->getURI().'index.php?page=post&s=list'
            .'&pid='.($this->getInput('p')?($this->getInput('p') -1)*20:'')
            .'&tags='.urlencode($this->getInput('t'))
        ) or $this->returnServerError('Could not request Booruprojec.');


	foreach($html->find('div[class=content] span') as $element) {
		$item = array();
		$item['uri'] = $this->getURI().$element->find('a', 0)->href;
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
