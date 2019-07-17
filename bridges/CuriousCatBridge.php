<?php
class CuriousCatBridge extends BridgeAbstract {
	const NAME = 'Curious Cat Bridge';
	const URI = 'https://curiouscat.me';
	const DESCRIPTION = 'Returns list of newest answers';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'username' => array(
			'name' => 'Username',
			'type' => 'text',
			'required' => true,
			'exampleValue' => 'koethekoethe',
		)
	));

	const CACHE_TIMEOUT = 3600;

	public function collectData() {

		$url = self::URI . '/api/v2/profile?username=' . urlencode($this->getInput('username'));

		$apiJson = getContents($url)
			or returnServerError('Could not request: ' . $url);

		$apiData = json_decode($apiJson, true);

		foreach($apiData['posts'] as $post) {
			$item = array();

			$item['author'] = 'Anonymous';

			if ($post['senderData']['id'] !== false) {
				$item['author'] = $post['senderData']['username'];
			}

			$item['uri'] = $this->getURI() . '/post/' . $post['id'];
			$item['title'] = $this->ellipsisTitle($post['comment']);

			$item['content'] = $this->processContent($post);
			$item['timestamp'] = $post['timestamp'];

			$this->items[] = $item;
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('username'))) {
			return self::URI . '/' . $this->getInput('username');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!is_null($this->getInput('username'))) {
			return $this->getInput('username') . ' - Curious Cat';
		}

		return parent::getName();
	}

	private function processContent($post) {

		$author = 'Anonymous';

		if ($post['senderData']['id'] !== false) {
			$authorUrl = self::URI . '/' . $post['senderData']['username'];

			$author = <<<EOD
<a href="{$authorUrl}">{$post['senderData']['username']}</a>
EOD;
		}

		$content = <<<EOD
<p>{$author} asked:</p>
<blockquote>{$post['comment']}</blockquote>
<p>{$post['addresseeData']['username']} answered:</p>
<blockquote>{$post['reply']}</blockquote>
EOD;

		return $content;
	}

	private function ellipsisTitle($text) {
		$length = 150;

		if (strlen($text) > $length) {
			$text = explode('<br>', wordwrap($text, $length, '<br>'));
			return $text[0] . '...';
		}

		return $text;
	}
}
