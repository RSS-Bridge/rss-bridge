<?php
class SoundCloudBridge extends BridgeAbstract{

	private $request;
	public $name;

	public function loadMetadatas() {

		$this->maintainer = "kranack";
		$this->name = "Soundcloud Bridge";
		$this->uri = "http://www.soundcloud.com/";
		$this->description = "Returns 10 newest music from user profile";
		$this->update = "2015-09-08";

		$this->parameters[] =
		'[
			{
				"name" : "username",
				"identifier" : "u"
			}
		]';

	}

  	const CLIENT_ID = '0aca19eae3843844e4053c6d8fdb7875';

	public function collectData(array $param){

		if (isset($param['u']) && !empty($param['u']))
		{
			$this->request = $param['u'];

			$res = json_decode(file_get_contents('https://api.soundcloud.com/resolve?url=http://www.soundcloud.com/'. urlencode($this->request) .'&client_id=' . self::CLIENT_ID)) or $this->returnError('No results for this query', 404);
			$tracks = json_decode(file_get_contents('https://api.soundcloud.com/users/'. urlencode($res->id) .'/tracks?client_id=' . self::CLIENT_ID)) or $this->returnError('No results for this user', 404);
		}
		else
		{
			$this->returnError('You must specify username', 400);
		}

		for ($i=0; $i < 10; $i++) {
		    $item = new \Item();
		    $item->name = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item->title = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item->content = '<audio src="'. $tracks[$i]->uri .'/stream?client_id='. self::CLIENT_ID .'">';
		    $item->id = 'https://soundcloud.com/'. urlencode($this->request) .'/'. urlencode($tracks[$i]->permalink);
		    $item->uri = 'https://soundcloud.com/'. urlencode($this->request) .'/'. urlencode($tracks[$i]->permalink);
		    $this->items[] = $item;
		}

    }
	public function getName(){
		return (!empty($this->name) ? $this->name .' - ' : '') . (!empty($this->request) ? $this->request : '');
	}

	public function getURI(){
		return 'https://www.soundcloud.com/';
	}

	public function getCacheDuration(){
		return 600; // 10 minutes
	}
}
