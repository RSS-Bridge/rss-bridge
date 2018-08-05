<?php
class ElloBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'Ello Bridge';
	const URI = 'https://ello.co/';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'Returns the newest posts for Ello';

	const PARAMETERS = array(
		'By User' => array(
			'u' => array(
				'name' => 'Username',
				'required' => true,
				'title' => 'Username'
			)
		),
		'Search' => array(
			's' => array(
				'name' => 'Search',
				'required' => true,
				'title' => 'Search'
			)
		)
	);

	public function collectData() {

		$header = array(
			'Authorization: Bearer ' . $this->getAPIKey()
		);

		if(!empty($this->getInput('u'))) {
			$postData = getContents(self::URI . 'api/v2/users/~' . urlencode($this->getInput('u')) . '/posts', $header) or
				returnServerError('Unable to query Ello API.');
		} else {
			$postData = getContents(self::URI . 'api/v2/posts?terms=' . urlencode($this->getInput('s')), $header) or
				returnServerError('Unable to query Ello API.');
		}

		$postData = json_decode($postData);
		$count = 0;
		foreach($postData->posts as $post) {

			$item = array();
			$item['author'] = $this->getUsername($post, $postData);
			$item['timestamp'] = strtotime($post->created_at);
			$item['title'] = strip_tags($this->findText($post->summary));
			$item['content'] = $this->getPostContent($post->body);
			$item['enclosures'] = $this->getEnclosures($post, $postData);
			$item['uri'] = self::URI . $item['author'] . '/post/' . $post->token;
			$content = $post->body;

			$this->items[] = $item;
			$count += 1;

		}

	}

	private function findText($path) {

		foreach($path as $summaryElement) {

			if($summaryElement->kind == 'text') {
				return $summaryElement->data;
			}

		}

		return '';

	}

	private function getPostContent($path) {

		$content = '';
		foreach($path as $summaryElement) {

			if($summaryElement->kind == 'text') {
				$content .= $summaryElement->data;
			} elseif ($summaryElement->kind == 'image') {
				$alt = '';
				if(property_exists($summaryElement->data, 'alt')) {
					$alt = $summaryElement->data->alt;
				}
				$content .= '<img src="' . $summaryElement->data->url . '" alt="' . $alt . '" />';
			}

		}

		return $content;

	}

	private function getEnclosures($post, $postData) {

		$assets = [];
		foreach($post->links->assets as $asset) {
			foreach($postData->linked->assets as $assetLink) {
				if($asset == $assetLink->id) {
					$assets[] = $assetLink->attachment->original->url;
					break;
				}
			}
		}

		return $assets;

	}

	private function getUsername($post, $postData) {

		foreach($postData->linked->users as $user) {
			if($user->id == $post->links->author->id) {
				return $user->username;
			}
		}

	}

	private function getAPIKey() {
		$cache = Cache::create('FileCache');
		$cache->setPath(CACHE_DIR);
		$cache->setParameters(['key']);
		$key = $cache->loadData();

		if($key == null) {
			$keyInfo = getContents(self::URI . 'api/webapp-token') or
				returnServerError('Unable to get token.');
			$key = json_decode($keyInfo)->token->access_token;
			$cache->saveData($key);
		}

		return $key;

	}

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Ello Bridge';
		}

		return parent::getName();
	}

}
