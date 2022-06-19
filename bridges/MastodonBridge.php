<?php

class MastodonBridge extends BridgeAbstract {
	// This script attempts to imitiate the behaviour of a read-only ActivityPub server
	// to read the outbox. This does not support instances that require HTTP signatures
	// for ActivityPub endpoints.

	// Note: Most PixelFed instances have ActivityPub outbox disabled,
	// so use the official feed: https://pixelfed.instance/users/username.atom (Posts only)

	const MAINTAINER = 'Austin Huang';
	const NAME = 'ActivityPub Bridge';
	const CACHE_TIMEOUT = 900; // 15mn
	const DESCRIPTION = 'Returns recent statuses. Supports Mastodon, Pleroma and Misskey, among others.';
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

	public function getName() {
		switch($this->queriedContext) {
			case 'By username':
				return $this->getInput('canusername');
			default: return parent::getName();
		}
	}

	private function getInstance() {
		preg_match('/^@[a-zA-Z0-9_]+@(.+)/', $this->getInput('canusername'), $matches);
		return $matches[1];
	}

	private function getUsername() {
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
				if ($link['type'] === 'application/activity+json') {
					return $link['href'];
				}
			}
		}

		return parent::getURI();
	}

	public function collectData() {
		$url = $this->getURI() . '/outbox?page=true';
		$content = json_decode(getContents($url, self::AP_HEADER), true);
		if ($content['id'] === $url) {
			foreach ($content['orderedItems'] as $status) {
				$users = array();
				$this->items[] = $this->parseItem($status, $users);
			}
		} else {
			throw new \Exception('Unexpected response from server.');
		}
	}

	protected function parseItem($content, &$users) {
		$item = array();
		switch ($content['type']) {
			case 'Announce': // boost
				if ($this->getInput('noboost')) {
					return null;
				}
				// We fetch the boosted content.
				try {
					$rtContent = json_decode(getContents($content['object'], self::AP_HEADER), true);
					if ($rtContent['attributedTo'] && isset($users[$rtContent['attributedTo']])) {
						$item['author'] = $users[$rtContent['attributedTo']];
						$item['title'] = 'Shared a status by ' . $item['author'] . ': ';
						$item = $this->parseObject($rtContent, $item);
					} else {
						// We fetch the author, since we cannot always assume the format of the URL.
						$user = json_decode(getContents($rtContent['attributedTo'], self::AP_HEADER), true);
						preg_match('/http(|s):\/\/([a-z0-9-\.]{0,})\//', $rtContent['attributedTo'], $matches);
						// We assume that the server name as indicated by the path is the actual server name,
						// since using webfinger to delegate domains is not officially supported, and it only
						// seems to work in one way.
						$rtUser = '@' . $user['preferredUsername'] . '@' . $matches[2];
						$users[$rtContent['attributedTo']] = $rtUser;
						$item['author'] = $rtUser;
						$item['title'] = 'Shared a status by ' . $rtUser . ': ';
						$item = $this->parseObject($rtContent, $item);
					}
				} catch (UnexpectedResponseException $th) {
					$item['title'] = 'Shared an unreachable status: ' . $content['object'];
					$item['content'] = $content['object'];
					$item['uri'] = $content['object'];
				}
				break;
			case 'Create':
				if ($this->getInput('norep') && isset($content['object']['inReplyTo'])) {
					return null;
				}
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
		$strippedContent = strip_tags($object['content']);

		if (mb_strlen($strippedContent) > 75) {
			$contentSubstring = mb_substr($strippedContent, 0, mb_strpos(wordwrap($strippedContent, 75), "\n"));
			$item['title'] .= $contentSubstring . '...';
		} else {
			$item['title'] .= $strippedContent;
		}
		$item['uri'] = $object['id'];
		foreach ($object['attachment'] as $attachment) {
			// Only process REMOTE pictures (prevent xss)
			if ($attachment['mediaType']
				&& preg_match('/^image\//', $attachment['mediaType'], $match)
				&& preg_match('/^http(s|):\/\//', $attachment['url'], $match)
			) {
				$item['content'] = $item['content'] . '<br /><img ';
				if ($attachment['name']) {
					$item['content'] .= sprintf('alt="%s" ', $attachment['name']);
				}
				$item['content'] .= sprintf('src="%s" />', $attachment['url']);
			}
		}
		return $item;
	}
}
