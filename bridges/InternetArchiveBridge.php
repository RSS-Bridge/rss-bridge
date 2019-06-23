<?php
class InternetArchiveBridge extends BridgeAbstract {
	const NAME = 'Internet Archive Bridge';
	const URI = 'https://archive.org';
	const DESCRIPTION = 'Returns newest uploads, posts and more from an account';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Account' => array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'exampleValue' => '@verifiedjoseph',
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'Uploads' => 'uploads',
					'Posts' => 'posts',
					'Reviews' => 'reviews',
					'collections' => 'collections',
					'Web Archives' => 'web-archive',
				),
				'defaultValue' => 'uploads',
			)
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $feedName = '';

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());
	}

	public function getURI() {

		if (!is_null($this->getInput('username')) && !is_null($this->getInput('content'))) {
			return self::URI . '/details/' . $this->processUsername() . '&tab=' . $this->getInput('content');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - Internet Archive';
		}

		return parent::getName();
	}

	private function processUsername() {

		if (substr($this->getInput('username'), 0, 1) != '@') {
			return '@' . $this->getInput('username');
		}

		return $this->getInput('username');
	}
}
