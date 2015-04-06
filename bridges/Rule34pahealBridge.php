<?php
/**
* RssBridgeRule34paheal
* Returns images from given page
* 2014-05-25
*
* @name Rule34paheal
* @homepage http://rule34.paheal.net/
* @description Returns images from given page
* @maintainer mitsukarenai
* @use1(p="page",t='tags")
*/
class Rule34pahealBridge extends BridgeAbstract{

    public function collectData(array $param){
	$page = 0;$tags='';
        if (isset($param['p'])) { 
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
        }
        if (isset($param['t'])) { 
            $tags = urlencode($param['t']); 
        }
        $html = file_get_html("http://rule34.paheal.net/post/list/$tags/$page") or $this->returnError('Could not request Rule34paheal.', 404);


	foreach($html->find('div[class=shm-image-list] div[class=shm-thumb]') as $element) {
		$item = new \Item();
		$item->uri = 'http://rule34.paheal.net'.$element->find('a', 0)->href;
		$item->postid = (int)preg_replace("/[^0-9]/",'', $element->find('img', 0)->getAttribute('id'));	
		$item->timestamp = time();
		$item->thumbnailUri = $element->find('img', 0)->src;
		$item->tags = $element->getAttribute('data-tags');
		$item->title = 'Rule34paheal | '.$item->postid;
		$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br>Tags: '.$item->tags;
		$this->items[] = $item; 
	}
    }

    public function getName(){
        return 'Rule34paheal';
    }

    public function getURI(){
        return 'http://rule34.paheal.net/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
