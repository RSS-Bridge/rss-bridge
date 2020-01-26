<?php
class SoundCloudBridge extends BridgeAbstract {

	const MAINTAINER = 'kranack, Roliga';
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

	private $feedIcon = null;
	private $clientIDCache = null;

	public function collectData(){
		$res = $this->apiGet('resolve', array(
			'url' => 'http://www.soundcloud.com/' . $this->getInput('u')
		)) or returnServerError('No results for this query');

		$this->feedIcon = $res->avatar_url;

		$tracks = $this->apiGet('users/' . urlencode($res->id) . '/tracks')
			or returnServerError('No results for this user');

		$numTracks = min(count($tracks), 10);
		for($i = 0; $i < $numTracks; $i++) {
			$item = array();
			$item['author'] = $tracks[$i]->user->username;
			$item['title'] = $tracks[$i]->user->username . ' - ' . $tracks[$i]->title;
			$item['timestamp'] = strtotime($tracks[$i]->created_at);
			$item['content'] = $tracks[$i]->description;
			$item['enclosures'] = array($tracks[$i]->uri
			. '/stream?client_id='
			. $this->getClientID());

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

	private function initClientIDCache(){
		if($this->clientIDCache !== null)
			return;

		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$this->clientIDCache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$this->clientIDCache->setScope(get_called_class());
		$this->clientIDCache->setKey(array('client_id'));
	}

	private function getClientID(){
		$this->initClientIDCache();

		$clientID = $this->clientIDCache->loadData();

		if($clientID == null) {
			return $this->refreshClientID();
		} else {
			return $clientID;
		}
	}

	private function refreshClientID(){
		$this->initClientIDCache();

		// Without url=http, this returns a 404
		$playerHTML = getContents('https://w.soundcloud.com/player/?url=http')
			or returnServerError('Unable to get player page.');
		$regex = '/widget-.+?\.js/';
		if(preg_match($regex, $playerHTML, $matches) == false)
			returnServerError('Unable to find widget JS URL.');
		$widgetURL = 'https://widget.sndcdn.com/' . $matches[0];

		$widgetJS = getContents($widgetURL)
			or returnServerError('Unable to get widget JS page.');
		$regex = '/client_id.*?"(.+?)"/';
		if(preg_match($regex, $widgetJS, $matches) == false)
			returnServerError('Unable to find client ID.');
		$clientID = $matches[1];

		$this->clientIDCache->saveData($clientID);
		return $clientID;
	}

	private function buildAPIURL($endpoint, $parameters){
		return 'https://api.soundcloud.com/'
			. $endpoint
			. '?'
			. http_build_query($parameters);
	}

	private function apiGet($endpoint, $parameters = array()){
		$parameters['client_id'] = $this->getClientID();

		try {
			return json_decode(getContents($this->buildAPIURL($endpoint, $parameters)));
		} catch (Exception $e) {
			// Retry once with refreshed client ID
			$parameters['client_id'] = $this->refreshClientID();
			return json_decode(getContents($this->buildAPIURL($endpoint, $parameters)));
		}
	}
}
