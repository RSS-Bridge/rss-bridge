<?php
class DanbooruBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Danbooru";
		$this->uri = "http://donmai.us/";
		$this->description = "Returns images from given page";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "page",
				"identifier" : "p"
			},
			{
				"name" : "tags",
				"identifier" : "t"
			}
		]';

	}

    public function collectData(array $param){
	$page = 1;$tags='';
        if (isset($param['p'])) { 
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
        }
        if (isset($param['t'])) { 
            $tags = urlencode($param['t']); 
        }
        $html = $this->file_get_html("http://donmai.us/posts?&page=$page&tags=$tags") or $this->returnError('Could not request Danbooru.', 404);
	foreach($html->find('div[id=posts] article') as $element) {
		$item = new \Item();
		$item->uri = 'http://donmai.us'.$element->find('a', 0)->href;
		$item->postid = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('data-id'));	
		$item->timestamp = time();
		$thumbnailUri = 'http://donmai.us'.$element->find('img', 0)->src;
		$item->tags = $element->find('img', 0)->getAttribute('alt');
		$item->title = 'Danbooru | '.$item->postid;
		$item->content = '<a href="' . $item->uri . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item->tags;
		$this->items[] = $item; 
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
