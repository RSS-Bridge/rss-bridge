<?php
class Rule34Bridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Rule34";
		$this->uri = "http://rule34.xxx/";
		$this->description = "Returns images from given page";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "page",
				"identifier" : "p",
				"type" : "number"
			},
			{
				"name" : "tags",
				"identifier" : "t"
			}
		]';
	}

    public function collectData(array $param){
	$page = 0;$tags='';
        if (isset($param['p'])) { 
		$page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
		$page = $page - 1;
		$page = $page * 50;
        }
        if (isset($param['t'])) { 
            $tags = urlencode($param['t']); 
        }
        $html = $this->file_get_html("http://rule34.xxx/index.php?page=post&s=list&tags=$tags&pid=$page") or $this->returnError('Could not request Rule34.', 404);


	foreach($html->find('div[class=content] span') as $element) {
		$item = new \Item();
		$item->uri = 'http://rule34.xxx/'.$element->find('a', 0)->href;
		$item->postid = (int)preg_replace("/[^0-9]/",'', $element->getAttribute('id'));	
		$item->timestamp = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item->tags = $element->find('img', 0)->getAttribute('alt');
		$item->title = 'Rule34 | '.$item->postid;
		$item->content = '<a href="' . $item->uri . '"><img src="' . $thumbnailUri . '" /></a><br>Tags: '.$item->tags;
		$this->items[] = $item; 
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
