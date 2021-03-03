<?php

class MastodonBridge extends FeedExpander {

	const MAINTAINER = 'husim0';
	const NAME = 'Mastodon Bridge';
	const CACHE_TIMEOUT = 900; // 15mn
	const DESCRIPTION = 'Returns toots';
	const URI = 'https://mastodon.social';

	const PARAMETERS = array(array(
		'canusername' => array(
			'name' => 'Canonical username (ex : @sebsauvage@framapiaf.org)',
			'required' => true,
		),
		'norep' => array(
			'name' => 'Without replies',
			'type' => 'checkbox',
			'title' => 'Only return initial toots'
		),
		'noboost' => array(
			'name' => 'Without boosts',
			'required' => false,
			'type' => 'checkbox',
			'title' => 'Hide boosts'
			)
		));

	public function getName(){
		switch($this->queriedContext) {
		case 'By username':
			return $this->getInput('canusername');
		default: return parent::getName();
		}
	}

	protected function parseItem($newItem){
		$item = parent::parseItem($newItem);

		$content = str_get_html($item['content']);
		$title = str_get_html($item['title']);

		$item['title'] = $content->plaintext;

		if(strlen($item['title']) > 75) {
			$item['title'] = substr($item['title'], 0, strpos(wordwrap($item['title'], 75), "\n")) . '...';
		}

		if(strpos($title, 'shared a status by') !== false) {
			if($this->getInput('noboost')) {
				return null;
			}

			preg_match('/shared a status by (\S{0,})/', $title, $matches);
			$item['title'] = 'Boost ' . $matches[1] . ' ' . $item['title'];
			$item['author'] = $matches[1];
		} else {
			$item['author'] = $this->getInput('canusername');
		}

		// Check if it's a initial toot or a response
		if($this->getInput('norep') && preg_match('/^@.+/', trim($content->plaintext))) {
			return null;
		}

		return $item;
	}

	private function getInstance(){
		preg_match('/^@[a-zA-Z0-9_]+@(.+)/', $this->getInput('canusername'), $matches);
		return $matches[1];
	}

	private function getUsername(){
		preg_match('/^@([a-zA-Z_0-9_]+)@.+/', $this->getInput('canusername'), $matches);
		return $matches[1];
	}

	public function getURI(){
		if($this->getInput('canusername'))
			return 'https://' . $this->getInstance() . '/@' . $this->getUsername() . '.rss';

		return parent::getURI();
	}

	public function collectData(){
		return $this->collectExpandableDatas($this->getURI());
	}
}
