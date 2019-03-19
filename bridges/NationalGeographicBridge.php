<?php
class NationalGeographicBridge extends BridgeAbstract {

	const CONTEXT_BY_TOPIC = 'By Topic';
	const PARAMETER_TOPIC = 'topic';
	const PARAMETER_FULL_ARTICLE = 'full';
	const TOPIC_MAGAZINE = 'Magazine';
	const TOPIC_LATEST_STORIES = 'Latest Stories';

	const NAME = 'National Geographic';
	const URI = 'https://www.nationalgeographic.com/';
	const DESCRIPTION = 'Fetches the latest articles from the National Geographic Magazine';
	const MAINTAINER = 'logmanoriginal';
	const PARAMETERS = array(
		self::CONTEXT_BY_TOPIC => array(
			self::PARAMETER_TOPIC => array(
				'name' => 'Topic',
				'type' => 'list',
				'values' => array(
					self::TOPIC_MAGAZINE => 'magazine',
					self::TOPIC_LATEST_STORIES => 'latest-stories'
				),
				'title' => 'Select your topic',
				'defaultValue' => 'Magazine'
			)
		),
		'global' => array(
			self::PARAMETER_FULL_ARTICLE => array(
				'name' => 'Full Article',
				'type' => 'checkbox',
				'title' => 'Enable to load full articles (takes longer)'
			)
		)
	);

	private $topicName = '';

	public function getURI() {
		switch ($this->queriedContext) {
			case self::CONTEXT_BY_TOPIC: {
				return self::URI . $this->getInput(self::PARAMETER_TOPIC);
			} break;
			default: {
				return parent::getURI();
			}
		}
	}

	public function collectData() {
		$this->topicName = $this->getTopicName($this->getInput(self::PARAMETER_TOPIC));

		switch($this->topicName) {
			case self::TOPIC_MAGAZINE: {
				return $this->collectMagazine();
			} break;
			case self::TOPIC_LATEST_STORIES: {
				return $this->collectLatestStories();
			} break;
			default: {
				returnServerError('Unknown topic: "' . $this->topicName . '"');
			}
		}
	}

	public function getName() {
		switch ($this->queriedContext) {
			case self::CONTEXT_BY_TOPIC: {
				return static::NAME . ': ' . $this->topicName;
			} break;
			default: {
				return parent::getName();
			}
		}
	}

	private function getTopicName($topic) {
		return array_search($topic, static::PARAMETERS[self::CONTEXT_BY_TOPIC][self::PARAMETER_TOPIC]['values']);
	}

	private function collectMagazine() {
		$uri = $this->getURI();

		$html = getSimpleHTMLDOM($uri)
			or returnServerError('Could not request ' . $uri);

		$script = $html->find('#lead-component script')[0];

		$json = json_decode($script->innertext, true);

		// This is probably going to break in the future, fix it then :)
		foreach($json['body']['0']['multilayout_promo_beta']['stories'] as $story) {
			$this->addStory($story);
		}
	}

	private function collectLatestStories() {
		$uri = self::URI . 'latest-stories/_jcr_content/content/hubfeed.promo-hub-feed-all-stories.json';

		$json_raw = getContents($uri)
			or returnServerError('Could not request ' . $uri);

		foreach(json_decode($json_raw, true) as $story) {
			$this->addStory($story);
		}
	}

	private function addStory($story) {
		$title = 'Unknown title';
		$content = '';

		foreach($story['components'] as $component) {
			switch($component['content_type']) {
				case 'title': {
					$title = $component['title']['text'];
				} break;
				case 'dek': {
					$content = $component['dek']['text'];
				} break;
			}
		}

		$item = array();

		$item['uid'] = $story['id'];
		$item['uri'] = $story['uri'];
		$item['title'] = $title;

		// if full article is requested!
		if ($this->getInput(self::PARAMETER_FULL_ARTICLE))
			$item['content'] = $this->getFullArticle($item['uri']);
		else
			$item['content'] = $content;

		if (isset($story['promo_image'])) {
			switch($story['promo_image']['content_type']) {
				case 'image': {
					$item['enclosures'][] = $story['promo_image']['image']['uri'];
				} break;
			}
		}

		if (isset($story['lead_media']) && empty($item['enclosures'])) {
			$media = $story['lead_media'];
			switch($media['content_type']) {
				case 'image': {
					$item['enclosures'][] = $media['image']['uri'];
				} break;
				// Note: immersive_media holds the same image, no need to add
			}
		}

		$this->items[] = $item;
	}

	private function getFullArticle($uri) {
		$html = getSimpleHTMLDOMCached($uri)
			or returnServerError('Could not load ' . $uri);

		$html = defaultLinkTo($html, $uri);

		$content = '';

		foreach($html->find('.content > .smartbody.text') as $paragraph) {
			$content .= $paragraph->outertext;
		}

		return $content;
	}
}
