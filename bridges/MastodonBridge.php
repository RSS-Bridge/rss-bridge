<?php

class MastodonBridge extends FeedExpander {
	// This script attempts to imitiate the behaviour of a read-only ActivityPub server
	// to read the outbox.

	// Note: Most PixelFed instances have ActivityPub outbox disabled,
	// use the official feed: https://pixelfed.instance/users/username.atom (Posts only)

	const MAINTAINER = 'Austin Huang';
	const NAME = 'Mastodon Bridge';
	const CACHE_TIMEOUT = 900; // 15mn
	const DESCRIPTION = 'Returns recent statuses. May support other ActivityPub-compatible accounts.';
	const URI = 'https://mastodon.social';

	const PARAMETERS = array(array(
		'canusername' => array(
			'name' => 'Canonical username',
			'exampleValue' => '@sebsauvage@framapiaf.org',
			'required' => true,
		),
		'norep' => array(
			'name' => 'Without replies',
			'type' => 'checkbox',
			'title' => 'Only return statuses that are not replies, as determined by relations (not mentions).'
		),
		'noboost' => array(
			'name' => 'Without boosts',
			'required' => false,
			'type' => 'checkbox',
			'title' => 'Hide boosts. Note that RSS-Bridge will fetch the original status from other federated instances.'
			)
		));

	const AP_HEADER = array(
			'Accept: application/activity+json'
		);

	public function getName(){
		switch($this->queriedContext) {
		case 'By username':
			return $this->getInput('canusername');
		default: return parent::getName();
		}
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
		if($this->getInput('canusername')) {
			// We parse webfinger to make sure the URL is correct. This is mostly because
			// MissKey uses user ID instead of the username in the endpoint, and also to
			// be compatible with future ActivityPub implementations.
			$resource = 'acct:' . $this->getUsername() . '@' . $this->getInstance();
			$webfingerUrl = 'https://' . $this->getInstance() . '/.well-known/webfinger?resource=' . $resource;
			$webfingerHeader = array(
				'Content-Type: application/jrd+json'
			);
			$webfinger = json_decode(getContents($webfingerUrl, $webfingerHeader), true);
			foreach ($webfinger['links'] as $link) {
				if ($link['type'] == 'application/activity+json')
					return $link['href'];
			}
		}

		return parent::getURI();
	}

	public function collectData(){
		$url = $this->getURI() . '/outbox?page=true';
		$content = json_decode(getContents($url, self::AP_HEADER), true);
		if ($content['id'] == $url) {
			foreach ($content['orderedItems'] as $status) {
				$this->items[] = $this->parseItem($status);
			}
		} else returnServerError('Unexpected response from server.');
	}

	protected function parseItem($content) {
		$item = array();
		switch ($content['type']) {
			case 'Announce': // boost
				if ($this->getInput('noboost')) return null;
				// We fetch the boosted content.
				try {
					$rtContent = json_decode(getContents($content['object'], self::AP_HEADER), true);
					// We fetch the author, since we cannot always assume the format of the URL.
					$user = json_decode(getContents($rtContent['attributedTo'], self::AP_HEADER), true);
					preg_match('/http(|s):\/\/([a-z0-9-\.]{0,})\//', $rtContent['attributedTo'], $matches);
					$rtUser = '@' . $user['preferredUsername'] . '@' . $matches[2];
					$item['author'] = $rtUser;
					$item['title'] = 'Shared a status by ' . $rtUser . ': ';
					$item = $this->parseObject($rtContent, $item);
				} catch (Throwable $th) {
					$item['title'] = 'Shared an unreachable status: ' . $content['object'];
					$item['content'] = $content['object'];
					$item['uri'] = $content['object'];
				}
				break;
			case 'Create':
				if ($this->getInput('norep') && $content['object']['inReplyTo']) return null;
				$item['author'] = $this->getInput('canusername');
				$item['title'] = '';
				$item = $this->parseObject($content['object'], $item);
		}
		$item['timestamp'] = $content['published'];
		$item['uid'] = $content['id'];
		return $item;
	}

	protected function parseObject($object, $item) {
		$item['content'] = $object['content'];
		if (strlen(strip_tags($object['content'])) > 75) {
			$item['title'] = $item['title'] .
							 substr(strip_tags($object['content']), 0, strpos(wordwrap(strip_tags($object['content']), 75), "\n")) . '...';
		} else $item['title'] = $item['title'] . strip_tags($object['content']);
		$item['uri'] = $object['id'];
		foreach ($object['attachment'] as $attachment) {
			// Only process REMOTE pictures (prevent xss)
			if ($attachment['mediaType'] && preg_match('/^image\//', $attachment['mediaType'], $match) &&
				preg_match('/^http(s|):\/\//', $attachment['url'], $match)) {
				$item['content'] = $item['content'] . '<br /><img ';
				if ($attachment['name']) $item['content'] = $item['content'] . 'alt="' . $attachment['name'] . '" ';
				$item['content'] = $item['content'] . 'src="' . $attachment['url'] . '" />';
			}
		}
		return $item;
	}
}
