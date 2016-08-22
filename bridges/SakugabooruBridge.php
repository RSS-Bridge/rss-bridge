<?php
class SakugabooruBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Sakugabooru";
		$this->uri = "http://sakuga.yshi.org/";
		$this->description = "Returns images from given page";

        $this->parameters[] = array(
          'p'=>array(
            'name'=>'page',
            'type'=>'number'
          ),
          't'=>array('name'=>'tags')
        );

	}

    public function collectData(array $param){
	$page = 1;$tags='';
        if (isset($param['p'])) {
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']);
        }
        if (isset($param['t'])) {
            $tags = urlencode($param['t']);
        }
        $html = $this->getSimpleHTMLDOM("http://sakuga.yshi.org/post?page=$page&tags=$tags") or $this->returnServerError('Could not request Sakugabooru.');
	$input_json = explode('Post.register(', $html);
	foreach($input_json as $element)
	 $data[] = preg_replace('/}\)(.*)/', '}', $element);
	unset($data[0]);

        foreach($data as $datai) {
	    $json = json_decode($datai, TRUE);
            $item = array();
            $item['uri'] = 'http://sakuga.yshi.org/post/show/'.$json['id'];
            $item['postid'] = $json['id'];
            $item['timestamp'] = $json['created_at'];
            $item['imageUri'] = $json['file_url'];
            $item['title'] = 'Sakugabooru | '.$json['id'];
            $item['content'] = '<a href="' . $item['imageUri'] . '"><img src="' . $json['preview_url'] . '" /></a><br>Tags: '.$json['tags'];
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
