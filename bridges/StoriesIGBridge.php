<?php
class StoriesIGBridge extends BridgeAbstract {

	const NAME = 'Instagram Stories';
	const URI = 'https://storiesig.com';
	const DESCRIPTION = 'Display Instagram Stories';
	const MAINTAINER = 'antoineturmel';
	const PARAMETERS = array(
		array(
			'username' => array(
				'name' => 'Instagram username',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert the username here'
			),
		)
	);

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Failed to receive ' . $this->getURI());

		$results = $html->find('article');

		foreach($results as $result) {

			$item = array();

			$item['title'] = $this->getInput('username') . ' story';
			$item['uri'] = $result->find('div.download', 0)->find('a', 0)->href;
			$item['author'] = $this->getInput('username');
			$item['timestamp'] = strtotime($result->find('time', 0)->datetime);
			$item['uid'] = $result->find('time', 0)->datetime;

			$item['content'] = $result;

			$this->items[] = $item;
		}
	}

	public function getURI(){
		$uri = self::URI . '/stories/';
		$uri .= urlencode($this->getInput('username'));
		return $uri;

		return parent::getURI();
	}

	public function getName() {

		if (!is_null($this->getInput('username'))) {
			return $this->getInput('username') . ' - ' . self::NAME;
		}

		return parent::getName();
	}
}
