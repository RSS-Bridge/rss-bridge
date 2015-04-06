<?php
/**
* RssBridgeBooruproject
* Returns images from given page
* 2014-05-25
*
* @name Booruproject
* @homepage http://booru.org/
* @description Returns images from given page and booruproject instance (****.booru.org)
* @maintainer mitsukarenai
* @use1(i="instance (required)", p="page", t="tags")
*/
class BooruprojectBridge extends BridgeAbstract{

    public function collectData(array $param){
	$page = 0; $tags = '';
        if (isset($param['p'])) { 
		$page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
		$page = $page - 1;
		$page = $page * 20;
        }
        if (isset($param['t'])) { 
            $tags = '&tags='.urlencode($param['t']); 
        }
	if (empty($param['i'])) {
		$this->returnError('Please enter a ***.booru.org instance.', 404);
	}
        $html = file_get_html("http://".$param['i'].".booru.org/index.php?page=post&s=list&pid=".$page.$tags) or $this->returnError('Could not request Booruproject.', 404);


	foreach($html->find('div[class=content] span') as $element) {
		$item = new \Item();
		$item->uri = 'http://'.$param['i'].'.booru.org/'.$element->find('a', 0)->href;
		$item->postid = (int)preg_replace("/[^0-9]/",'', $element->find('a', 0)->getAttribute('id'));	
		$item->timestamp = time();
		$item->thumbnailUri = $element->find('img', 0)->src;
		$item->tags = $element->find('img', 0)->getAttribute('title');
		$item->title = 'Booruproject '.$param['i'].' | '.$item->postid;
		$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br>Tags: '.$item->tags;
		$this->items[] = $item; 
	}
    }

    public function getName(){
        return 'Booruproject';
    }

    public function getURI(){
        return 'http://booru.org/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
