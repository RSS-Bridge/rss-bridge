<?php
class YouTubeCommunityTabBridge extends BridgeAbstract {
	const NAME = 'YouTube Community Tab Bridge';
	const URI = 'https://www.youtube.com';
	const DESCRIPTION = 'Returns posts from a channel\'s community tab';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'channel' => array(
			'name' => 'Channel ID',
			'type' => 'text',
			'required' => true,
			'exampleValue' => 'UCULkRHBdLC5ZcEQBaL0oYHQ'
		),
	));

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $feedName = '';
	private $itemTitle = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$html = defaultLinkTo($html, $this->getURI());

		$json = $this->extractJson(
			$html->find('script', 26)->innertext
		);

		$this->feedName = $json->header->c4TabbedHeaderRenderer->title;

		if ($this->hasCommunityTab($json) === false) {
			returnServerError('Channel does not have a community tab');
		}

		foreach ($this->getCommunityPosts($json) as $post) {
			$this->itemTitle = '';
			$details = $post->backstagePostThreadRenderer->post->backstagePostRenderer;

			$item = array();
			$item['uri'] = self::URI . '/post/' . $details->postId;
			$item['author'] = $details->authorText->simpleText;
			$item['content'] = '';

			if (isset($details->contentText)) {
				$this->itemTitle = $this->ellipsisTitle(nl2br($details->contentText->runs[0]->text));
				$item['content'] = nl2br($details->contentText->runs[0]->text);
			}

			$item['content'] .= $this->getAttachments($details);
			$item['title'] = $this->itemTitle;

			$this->items[] = $item;
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('channel'))) {
			return self::URI . '/channel/' . $this->getInput('channel') . '/community';
		}

		return parent::getURI();
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - YouTube Community Tab';
		}

		return parent::getName();
	}

	/**
	 * Extract JSON from page
	 */
	private function extractJson($script) {

		if (preg_match('/window\["ytInitialData"\] = (.*);/', $script, $parts) === false) {
			returnServerError('Failed to extract data from page');
		}

		$data = json_decode($parts[1]);

		if ($data === false) {
			returnServerError('Failed to decode extracted data');
		}

		return $data;
	}

	/**
	 * Check if channel has a community tab
	 */
	private function hasCommunityTab($json) {

		foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
			if (isset($tab->tabRenderer) && $tab->tabRenderer->title === 'Community') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get community tab posts
	 */
	private function getCommunityPosts($json) {

		foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
			if ($tab->tabRenderer->title === 'Community') {
				return $tab->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer->contents;
			}
		}
	}

	/**
	 * Get attachments for posts
	 */
	private function getAttachments($details) {
		$content = '';

		if (isset($details->backstageAttachment)) {
			$attachments = $details->backstageAttachment;

			// Video
			if (isset($attachments->videoRenderer) && isset($attachments->videoRenderer->videoId)) {
				if (empty($this->itemTitle)) {
					$this->itemTitle = $this->feedName . ' posted a video';
				}

				$content = <<<EOD
<iframe width="100%" height="410" src="https://www.youtube.com/embed/{$attachments->videoRenderer->videoId}" 
frameborder="0" allow="encrypted-media;" allowfullscreen></iframe>
EOD;
			}

			// Image
			if (isset($attachments->backstageImageRenderer)) {
				if (empty($this->itemTitle)) {
					$this->itemTitle = $this->feedName . ' posted an image';
				}

				$lastThumb = end($attachments->backstageImageRenderer->image->thumbnails);

				$content = <<<EOD
<p><img src="{$lastThumb->url}"></p>
EOD;
			}

			// Poll
			if (isset($attachments->pollRenderer)) {
				if (empty($this->itemTitle)) {
					$this->itemTitle = $this->feedName . ' posted a poll';
				}

				$pollChoices = '';

				foreach ($attachments->pollRenderer->choices as $choice) {
					$pollChoices .= <<<EOD
<li>{$choice->text->runs[0]->text}</li>
EOD;
				}

				$content = <<<EOD
<hr><p>Poll ({$attachments->pollRenderer->totalVotes->runs[0]->text})<br><ul>{$pollChoices}</ul><p>
EOD;
			}
		}

		return $content;
	}

	/*
		From TelegramBridge
	*/
	private function ellipsisTitle($text) {
		$length = 100;

		if (strlen($text) > $length) {
			$text = explode('<br>', wordwrap($text, $length, '<br>'));
			return $text[0] . '...';
		}

		return $text;
	}
}
