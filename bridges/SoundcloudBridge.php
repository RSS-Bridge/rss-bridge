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
		),
		't' => array(
			'name' => 'type',
			'type' => 'list',
			'defaultValue' => 'tracks',
			'values' => array(
				'Tracks' => 'tracks',
				'Playlists' => 'playlists'
			)
		)
	));

	private $feedTitle = null;
	private $feedIcon = null;
	private $clientIDCache = null;

	private $clientIdRegex = '/client_id.*?"(.+?)"/';
	private $widgetRegex = '/widget-.+?\.js/';

	public function collectData(){
		$res = $this->apiGet('resolve', array(
			'url' => 'https://soundcloud.com/' . $this->getInput('u')
		)) or returnServerError('No results for this query');

		$this->feedTitle = $res->username;
		$this->feedIcon = $res->avatar_url;

		$tracks = $this->apiGet(
			'users/' . urlencode($res->id) . '/' . $this->getInput('t'),
			array('limit' => 31)
		) or returnServerError('No results for this user/playlist');

		foreach ($tracks->collection as $index => $track) {
			$item = array();
			$item['author'] = $track->user->username;
			$item['title'] = $track->user->username . ' - ' . $track->title;
			$item['timestamp'] = strtotime($track->created_at);
			$item['content'] = nl2br($track->description);
			$item['enclosures'][] = $track->artwork_url;

			$item['id'] = self::URI
				. urlencode($this->getInput('u'))
				. '/'
				. urlencode($track->permalink);
			$item['uri'] = self::URI
				. urlencode($this->getInput('u'))
				. '/'
				. urlencode($track->permalink);
			$this->items[] = $item;

			if (count($this->items) >= 10) {
				break;
			}
		}
	}

	public function getIcon(){
		if ($this->feedIcon) {
			return $this->feedIcon;
		}

		return parent::getIcon();
	}

	public function getURI(){
		return 'https://soundcloud.com/' . $this->getInput('u');
	}

	public function getName(){
		if($this->feedTitle) {
			return $this->feedTitle . ' - ' . self::NAME;
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

		// Extract widget JS filenames from player page
		if(preg_match_all($this->widgetRegex, $playerHTML, $matches) == false)
			returnServerError('Unable to find widget JS URL.');

		$clientID = '';

		// Loop widget js files and extract client ID
		foreach ($matches[0] as $widgetFile) {
			$widgetURL = 'https://widget.sndcdn.com/' . $widgetFile;

			$widgetJS = getContents($widgetURL)
				or returnServerError('Unable to get widget JS page.');

			if(preg_match($this->clientIdRegex, $widgetJS, $matches)) {
				$clientID = $matches[1];
				$this->clientIDCache->saveData($clientID);

				return $clientID;
			}
		}

		if (empty($clientID)) {
			returnServerError('Unable to find client ID.');
		}
	}

	private function buildAPIURL($endpoint, $parameters){
		return 'https://api-v2.soundcloud.com/'
			. $endpoint
			. '?'
			. http_build_query($parameters);
	}

	private function apiGet($endpoint, $parameters = array()) {
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
