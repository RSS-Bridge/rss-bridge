<?php
class KonachanBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Konachan";
		$this->uri = "http://konachan.com/";
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
	$page = 1;$tags='';
        if (isset($param['p'])) { 
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
        }
        if (isset($param['t'])) { 
            $tags = urlencode($param['t']); 
        }
        $html = $this->file_get_html("http://konachan.com/post?page=$page&tags=$tags") or $this->returnError('Could not request Konachan.', 404);
	$input_json = explode('Post.register(', $html);
	foreach($input_json as $element)
	 $data[] = preg_replace('/}\)(.*)/', '}', $element);
	unset($data[0]);
    
        foreach($data as $datai) {
	    $json = json_decode($datai, TRUE);
            $item = new \Item();
            $item->uri = 'http://konachan.com/post/show/'.$json['id'];
            $item->postid = $json['id'];
            $item->timestamp = $json['created_at'];
            $item->imageUri = $json['file_url'];
            $item->title = 'Konachan | '.$json['id'];
            $item->content = '<a href="' . $item->imageUri . '"><img src="' . $json['preview_url'] . '" /></a><br>Tags: '.$json['tags']; 
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
