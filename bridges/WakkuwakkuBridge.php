<?php
/**
* RssBridgeWakkuWakku 
* Returns images from given page
* 2014-05-25
*
* @name WakkuWakku
* @homepage http://wakku.to/
* @description Returns images from given page
* @maintainer mitsukarenai
* @use1(p="page",t="tags")
*/
class WakkuWakkuBridge extends BridgeAbstract{

    public function collectData(array $param){
	$page = 1;$tags='';
        if (isset($param['p'])) { 
            $page = (int)preg_replace("/[^0-9]/",'', $param['p']); 
        }
        if (isset($param['t'])) { 
            $tags = urlencode($param['t']); 
        }
        $html = file_get_html("http://wakku.to/post?page=$page&tags=$tags") or $this->returnError('Could not request WakkuWakku.', 404);
	$input_json = explode('Post.register(', $html);
	foreach($input_json as $element)
	 $data[] = preg_replace('/}\)(.*)/', '}', $element);
	unset($data[0]);
    
        foreach($data as $datai) {
	    $json = json_decode($datai, TRUE);
            $item = new \Item();
            $item->uri = 'http://wakku.to/post/show/'.$json['id'];
            $item->postid = $json['id'];
            $item->timestamp = strtotime($json['created_at']);
            $item->imageUri = 'http://wakku.to/'.$json['file_url'];
            $item->thumbnailUri = 'http://wakku.to/'.$json['preview_url'];
            $item->title = 'WakkuWakku | '.$json['id'];
            $item->content = '<a href="' . $item->imageUri . '"><img src="' . $item->thumbnailUri . '" /></a><br>Tags: '.$json['tags']; 
            if(isset($json['id']) and !empty($json['id'])) $this->items[] = $item;
        }
    }

    public function getName(){
        return 'WakkuWakku';
    }

    public function getURI(){
        return 'http://wakku.to/';
    }

    public function getCacheDuration(){
        return 1800; // 30 minutes
    }
}
