<?php
class SoundCloudBridge extends BridgeAbstract {

	const MAINTAINER = 'kranack';
	const NAME = 'Soundcloud Bridge';
	const URI = 'https://soundcloud.com/';
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = 'Returns 10 newest music from user profile';

	const PARAMETERS = array( array(
		'u' => array(
			'name' => 'username',
			'required' => true
		)
	));

	const CLIENT_ID = 'W0KEWWILAjDiRH89X0jpwzuq6rbSK08R';

	private $feedIcon = null;

	public function collectData(){

		$res = json_decode(getContents(
			'https://api.soundcloud.com/resolve?url=http://www.soundcloud.com/'
			. urlencode($this->getInput('u'))
			. '&client_id='
			. self::CLIENT_ID
		)) or returnServerError('No results for this query');

		$this->feedIcon = $res->avatar_url;

		$tracks = json_decode(getContents(
			'https://api.soundcloud.com/users/'
			. urlencode($res->id)
			. '/tracks?client_id='
			. self::CLIENT_ID
		)) or returnServerError('No results for this user');

		$numTracks = min(count($tracks), 10);
		for($i = 0; $i < $numTracks; $i++) {
			$item = array();
			$item['author'] = $tracks[$i]->user->username;
			$item['title'] = $tracks[$i]->user->username . ' - ' . $tracks[$i]->title;
			$item['timestamp'] = strtotime($tracks[$i]->created_at);
			$item['content'] = $tracks[$i]->description;
			$item['enclosures'] = array($tracks[$i]->uri
			. '/stream?client_id='
			. self::CLIENT_ID);

			$item['id'] = self::URI
				. urlencode($this->getInput('u'))
				. '/'
				. urlencode($tracks[$i]->permalink);
			$item['uri'] = self::URI
				. urlencode($this->getInput('u'))
				. '/'
				. urlencode($tracks[$i]->permalink);
			$this->items[] = $item;
		}

	}

	public function getIcon(){
		if ($this->feedIcon) {
			return $this->feedIcon;
		}

		return parent::getIcon();
	}

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return self::NAME . ' - ' . $this->getInput('u');
		}

		return parent::getName();
	}
}
