<?php
/**
* SoundcloudBridge
* Returns the newest music from user
*
* @name Soundcloud Bridge
* @homepage http://www.soundcloud.com/
* @description Returns 10 newest music from user profile 
* @maintainer kranack
* @update 2014-07-24
* @use1(u="username")
*
*/
class SoundCloudBridge extends BridgeAbstract{
    
	private $request;
	private $name;
    
	public function collectData(array $param){
		
		if (isset($param['u']) && !empty($param['u']))
		{
			$this->request = $param['u'];
           	
			$res = json_decode(file_get_contents('http://api.soundcloud.com/resolve.json?url=http://www.soundcloud.com/'. urlencode($this->request) .'&consumer_key=apigee')) or $this->returnError('No results for this query', 404);
			$tracks = json_decode(file_get_contents('http://api.soundcloud.com/users/'. urlencode($res->id) .'/tracks.json?consumer_key=apigee')) or $this->returnError('No results for this user', 404);
		} 
		else
		{
			$this->returnError('You must specify username', 400);
		}

		for ($i=0; $i < 10; $i++) {
		    $item = new \Item();
		    $item->name = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item->title = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item->content = '<audio src="'. $tracks[$i]->uri .'/stream?consumer_key=apigee">';
		    $item->id = 'https://soundcloud.com/'. urlencode($this->request) .'/'. urlencode($tracks[$i]->permalink);
		    $item->uri = 'https://soundcloud.com/'. urlencode($this->request) .'/'. urlencode($tracks[$i]->permalink);
		    $this->items[] = $item;
		}
		
    }
	public function getName(){
		return (!empty($this->name) ? $this->name .' - ' : '') .'Soundcloud Bridge';
	}

	public function getURI(){
		return 'http://www.soundcloud.com/';
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}
