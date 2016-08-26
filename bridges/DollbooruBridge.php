<?php
class DollbooruBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Dollbooru";
		$this->uri = "http://dollbooru.org/";
		$this->description = "Returns images from given page";


        $this->parameters[]  = array(
          'p'=>array(
            'name'=>'page',
            'type'=>'number'
          ),
          't'=>array('name'=>'tags')
        );
	}

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
	$page = 0;$tags='';
        if (isset($param['p']['value'])) {
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']['value']);
        }
        if (isset($param['t']['value'])) {
            $tags = urlencode($param['t']['value']);
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
