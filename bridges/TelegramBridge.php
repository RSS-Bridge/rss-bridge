<?php
class TelegramBridge extends BridgeAbstract {
	const NAME = 'Telegram Bridge';
	const URI = 'https://t.me';
	const DESCRIPTION = 'Returns newest post from a public Telegram channel';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'exampleValue' => '@telegram',
			)
		)
	);

	const CACHE_TIMEOUT = 600; // 15 mins

	private $backgroundImageRegex = "/background-image:url\('(.*)'\)/";
	
	public function collectData() {
		
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		foreach($html->find('div.tgme_widget_message_wrap.js-widget_message_wrap') as $index => $messageDiv) {
			$item = array();

			$item['uri'] = $this->processUri($messageDiv);
			//$item['title']
			$item['content'] = $this->processContent($messageDiv);
			$item['timestamp'] = $this->processDate($messageDiv);
			//$item['enclosures'][];

			$this->items[] = $item;
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('username'))) {
			return self::URI . '/s/' . $this->processUsername();
		}

		return parent::getURI();
	}

	/*public function getName() {

		if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
			$parameters = $this->getParameters();

			$editionValues = array_flip($parameters[0]['edition']['values']);
			$categoryValues = array_flip($parameters[0]['category']['values']);

			return $categoryValues[$this->getInput('category')] . ' - ' .
				$editionValues[$this->getInput('edition')] . ' - Brut.';
		}

		return parent::getName();
	}*/

	private function processUsername() {

		if (substr($this->getInput('username'), 0, 1) === '@') {
			return substr($this->getInput('username'), 1);
		}

		return $this->getInput('username');
	}
	
	private function processUri($messageDiv) {

		return $messageDiv->find('a.tgme_widget_message_date', 0)->href;

	}
	
	private function processTitle($messageDiv) {	

	}
	
	private function processContent($messageDiv) {
		$message = '';

		if ($messageDiv->find('a.tgme_widget_message_reply', 0)) {
			$message = $this->processReply($messageDiv);
		}
		
		if ($messageDiv->find('div.tgme_widget_message_poll', 0)) {
			//$message .= $messageDiv->find('div.tgme_widget_message_poll', 0);
		}

		if ($messageDiv->find('video', 0)) {
			$message = $this->processVideo($messageDiv);
		}

		if ($messageDiv->find('a.tgme_widget_message_photo_wrap', 0)) {
			$message = $this->processPhoto($messageDiv);
		}

		if ($messageDiv->find('div.tgme_widget_message_text.js-message_text', 0)) {
			$message .= $messageDiv->find('div.tgme_widget_message_text.js-message_text', 0);
		}

		return $message;

	}
	
	private function processReply($messageDiv) {

		$reply = $messageDiv->find('a.tgme_widget_message_reply', 0);
		
		return <<<EOD
<blockquote>{$reply->find('span.tgme_widget_message_author_name', 0)->plaintext}<br>
{$reply->find('div.tgme_widget_message_text', 0)->innertext} <a href="{$reply->href}">{$reply->href}</a></blockquote><hr>
EOD;

	}
	
	private function processPoll($messageDiv) {

		preg_match($this->backgroundImageRegex, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);

		return <<<EOD
<video controls="" poster="{$photo[1]}" preload="none">
	<source src="{$messageDiv->find('video', 0)->src}" type="video/mp4">
</video>
EOD;

	}
	
	private function processVideo($messageDiv) {

		preg_match($this->backgroundImageRegex, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);

		return <<<EOD
<video controls="" poster="{$photo[1]}" preload="none">
	<source src="{$messageDiv->find('video', 0)->src}" type="video/mp4">
</video>
EOD;

	}
	
	private function processPhoto($messageDiv) {
	
		preg_match($this->backgroundImageRegex, $messageDiv->find('a.tgme_widget_message_photo_wrap', 0)->style, $photo);

		return <<<EOD
<a href="{$messageDiv->find('a.tgme_widget_message_photo_wrap', 0)->href}"><img src="{$photo[1]}"/></a>
EOD;	
	}
	
	private function processDate($messageDiv) {

		return $messageDiv->find('time', 0)->datetime;

	}
}
