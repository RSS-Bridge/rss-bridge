<?php
class TikTokBridge extends BridgeAbstract {
	const NAME = 'TikTok Bridge';
	const URI = 'https://www.tiktok.com';
	const DESCRIPTION = 'Returns newest posts for a user';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'username' => array(
			'name' => 'Username',
			'type' => 'text',
			'required' => true,
			'exampleValue' => '@tiktok',
		)
	));

	const CACHE_TIMEOUT = 1900;

	private $feedName = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI());

		$this->feedName = htmlspecialchars_decode($html->find('h1', 0)->plaintext);

		foreach ($html->find('div.tiktok-x6y88p-DivItemContainerV2') as $div) {
			$item = [];

			$link = $div->find('a', 0)->href;
			$image = $div->find('img', 0)->src;
			$views = $div->find('strong.video-count', 0)->plaintext;

			$item['uri'] = $link;
			$item['title'] = $div->find('a', 1)->plaintext;
			$item['enclosures'][] = $image;

			$item['content'] = <<<EOD
<a href="{$link}"><img src="{$image}"/></a>
<p>{$views} views<p>
EOD;

			$this->items[] = $item;
		}
	}

	public function getURI() {
		if (is_null($this->getInput('username')) === false) {
			return self::URI . '/' . $this->processUsername();
		}

		return parent::getURI();
	}

	public function getName() {
		if (empty($this->feedName) === false) {
			return $this->feedName . ' (' . $this->processUsername() . ') - TikTok';
		}

		return parent::getName();
	}

	private function processUsername() {
		if (substr($this->getInput('username'), 0, 1) !== '@') {
			return '@' . $this->getInput('username');
		}

		return $this->getInput('username');
	}
}
