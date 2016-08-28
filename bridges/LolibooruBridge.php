<?php
class LolibooruBridge extends BridgeAbstract{

	public $maintainer = "mitsukarenai";
	public $name = "Lolibooru";
	public $uri = "http://lolibooru.moe/";
	public $description = "Returns images from given page and tags";

    public $parameters = array( array(
        'p'=>array(
            'name'=>'page',
            'type'=>'number'
        ),
        't'=>array('name'=>'tags')
    ));

    public function collectData(){
	$page = 1; $tags = '';
        if ($this->getInput('p')) {
            $page = (int)preg_replace("/[^0-9]/",'', $this->getInput('p'));
        }
        if ($this->getInput('t')) {
            $tags = urlencode($this->getInput('t'));
        }
        $html = $this->getSimpleHTMLDOM("http://lolibooru.moe/post?page=$page&tags=$tags") or $this->returnServerError('Could not request Lolibooru.');
	$input_json = explode('Post.register(', $html);
	foreach($input_json as $element)
	 $data[] = preg_replace('/}\)(.*)/', '}', $element);
	unset($data[0]);

        foreach($data as $datai) {
	    $json = json_decode($datai, TRUE);
            $item = array();
            $item['uri'] = 'http://lolibooru.moe/post/show/'.$json['id'];
            $item['postid'] = $json['id'];
            $item['timestamp'] = $json['created_at'];
            $item['imageUri'] = $json['file_url'];
            $item['title'] = 'Lolibooru | '.$json['id'];
            $item['content'] = '<a href="' . $item['imageUri'] . '"><img src="' . $json['preview_url'] . '" /></a><br>Tags: '.$json['tags'];
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
