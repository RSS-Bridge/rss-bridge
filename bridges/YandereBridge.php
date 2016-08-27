<?php
class YandereBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Yande.re";
	public $uri = "https://yande.re/";
	public $description = "Returns images from given page and tags";

    public $parameters = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
        $param=$this->parameters[$this->queriedContext];
	$page = 1; $tags = '';
        if (isset($param['p']['value'])) {
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']['value']);
        }
        if (isset($param['t']['value'])) {
            $tags = urlencode($param['t']['value']);
        }
        $html = $this->getSimpleHTMLDOM("https://yande.re/post?page=$page&tags=$tags") or $this->returnServerError('Could not request Yandere.');
	$input_json = explode('Post.register(', $html);
	foreach($input_json as $element)
	 $data[] = preg_replace('/}\)(.*)/', '}', $element);
	unset($data[0]);

        foreach($data as $datai) {
	    $json = json_decode($datai, TRUE);
            $item = array();
            $item['uri'] = 'http://yande.re/post/show/'.$json['id'];
            $item['postid'] = $json['id'];
            $item['timestamp'] = $json['created_at'];
            $item['imageUri'] = $json['file_url'];
            $item['title'] = 'Yandere | '.$json['id'];
            $item['content'] = '<a href="' . $item['imageUri'] . '"><img src="' . $json['preview_url'] . '" /></a><br>Tags: '.$json['tags'];
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
