<?php
class BooruprojectBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Booruproject";
		$this->uri = "http://booru.org/";
		$this->description = "Returns images from given page and booruproject instance (****.booru.org)";
		$this->update = "2016-08-15";

		$this->parameters[] =
		'[
			{
				"name" : "instance (required)",
				"required" : true,
				"identifier" : "i"
			},
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
	$page = 0; $tags = '';
        if (!empty($param['p'])) { 
		$page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
		$page = $page - 1;
		$page = $page * 20;
        }
        if (!empty($param['t'])) { 
            $tags = '&tags='.urlencode($param['t']); 
        }
	if (empty($param['i'])) {
		$this->returnError('Please enter a ***.booru.org instance.', 404);
	}
        $html = $this->file_get_html("http://".$param['i'].".booru.org/index.php?page=post&s=list&pid=".$page.$tags) or $this->returnError('Could not request Booruproject.', 404);


	foreach($html->find('div[class=content] span') as $element) {
		$item = new \Item();
		$item->uri = 'http://'.$param['i'].'.booru.org/'.$element->find('a', 0)->href;
		$item->postid = (int)preg_replace("/[^0-9]/",'', $element->find('a', 0)->getAttribute('id'));	
		$item->timestamp = time();
		$item->tags = $element->find('img', 0)->getAttribute('title');
		$item->title = 'Booruproject '.$param['i'].' | '.$item->postid;
		$item->content = '<a href="' . $item->uri . '"><img src="' . $element->find('img', 0)->src . '" /></a><br>Tags: '.$item->tags;
		$this->items[] = $item; 
	}
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
