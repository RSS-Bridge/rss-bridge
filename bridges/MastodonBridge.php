<?php

class MastodonBridge extends FeedExpander {

	const MAINTAINER = 'husim0';
	const NAME = 'Mastodon Bridge';
	const CACHE_TIMEOUT = 0; // 1h
	const DESCRIPTION = 'Returns toots';
	const URI = 'https://mastodon.social';

	const PARAMETERS = [[
		'canusername' => [
			'name' => 'Canonical username (ex : @sebsauvage@framapiaf.org)',
			'required' => true,
		],
		'norep' => [
			'name' => 'Without replies',
			'type' => 'checkbox',
			'title' => 'Only return initial toots'
		],
		'noboost' => [
			'name' => 'Without boosts',
			'required' => false,
			'type' => 'checkbox',
			'title' => 'Hide boosts'
		]
	]];

	public function getName(){
		switch($this->queriedContext) {
		case 'By username':
			$param = 'canusername';
			break;
		default: return parent::getName();
		}
		
		return $this->getInput($param);
	}

	protected function parseItem($newItem){
		$item = parent::parseItem($newItem);

		$content = str_get_html($item['content']);
		$title = str_get_html($item['title']);

		$item['title'] = substr($content->plaintext,0,75) . (strlen($content->plaintext) >= 75 ? '...' : '');

		if(strpos($title, 'shared a status by') !== false) {
			if($this->getInput('noboost')) {
				return null;
			}

			preg_match("/shared a status by (\S{0,})/", $title, $matches);
			$item['title'] = 'Boost ' . $matches[1] . ' ' . $item['title'];
			$item['author'] = $matches[1];
		} else {
			$item['author'] = $this->getInput('canusername');
		}

		// Check if it's a initial toot or a response
		if($this->getInput('norep') && preg_match("/^@.+/", trim($content->plaintext))) {
			return null;
		}

		return $item;
	}

	public function getInstance(){
		preg_match("/^@[a-zA-Z0-9_]+@(.+)/", $this->getInput('canusername'), $matches);
		return $matches[1];
	}

	public function getUsername(){
		preg_match("/^@([a-zA-Z_0-9_]+)@.+/", $this->getInput('canusername'), $matches);
		return $matches[1];
	}

	public function getURI(){
		return 'https://' . $this->getInstance() . '/users/' . $this->getUsername() . '.atom';
	}

	public function collectData(){
		try{
			$this->collectExpandableDatas($this->getURI());
		} catch (Exception $e) {
			$this->collectExpandableDatas($this->getURI());
		}
	}
}
