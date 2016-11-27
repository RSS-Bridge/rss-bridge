<?php
class SoundCloudBridge extends BridgeAbstract{

	const MAINTAINER = "kranack";
	const NAME = "Soundcloud Bridge";
	const URI = "https://soundcloud.com/";
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = "Returns 10 newest music from user profile";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'username',
            'required'=>true
        )
    ));

  	const CLIENT_ID = '0aca19eae3843844e4053c6d8fdb7875';

	public function collectData(){

        $res = json_decode(getContents(
            'https://api.soundcloud.com/resolve?url=http://www.soundcloud.com/'
            . urlencode($this->getInput('u'))
            .'&client_id=' . self::CLIENT_ID
        )) or returnServerError('No results for this query');
        $tracks = json_decode(getContents(
            'https://api.soundcloud.com/users/'
            . urlencode($res->id)
            .'/tracks?client_id=' . self::CLIENT_ID
        )) or returnServerError('No results for this user');

		for ($i=0; $i < 10; $i++) {
		    $item = array();
		    $item['author'] = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item['title'] = $tracks[$i]->user->username .' - '. $tracks[$i]->title;
		    $item['content'] = '<audio src="'. $tracks[$i]->uri .'/stream?client_id='. self::CLIENT_ID .'">';
            $item['id'] = self::URI
                . urlencode($this->getInput('u')) .'/'
                . urlencode($tracks[$i]->permalink);
            $item['uri'] = self::URI
                . urlencode($this->getInput('u')) .'/'
                . urlencode($tracks[$i]->permalink);
		    $this->items[] = $item;
		}

    }
	public function getName(){
		return self::NAME .' - '. $this->getInput('u');
	}
}
