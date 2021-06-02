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
				return $this->collectStories('magazine');
			} break;
			case self::TOPIC_LATEST_STORIES: {
				return $this->collectStories('latest');
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

	private function collectStories($topic) {

		$uri = '';
		if ($topic == 'latest') {
			$uri = self::URI . '/pages/topic/latest-stories';
		} else {
			$uri = $this->getURI();
		}

		$html = getSimpleHTMLDOM($uri);
		$articles = $html->find('article');

		foreach($articles as $article) {
			// Reference: https://simplehtmldom.sourceforge.io/manual_api.htm#api
			$article_url = $article->childNodes(0)->href;

			/* National Geographic have two types of articles:
			*	1. Normal article
			* 2. Interactive (require JS and don't have any useful info like timestamp so it won't supported)
			*
			*/
			if(strpos($article_url, '\/graphics\/') !== false) {
				break;
			}

			$article_html = getSimpleHTMLDOM($article_url);

			$this->addStory($article_html, $article_url);
		}

	}

	private function getJSONBlock($html, $selector, $index) {
		$json_block = $html->find($selector, $index)->innertext;
		return json_decode($json_block, true);
	}

	private function getArticleInfo($story_html) {
		$json = $this->getJSONBlock($story_html, 'script[type="application/ld+json"]', 0);
		$timestamp = $json['datePublished'];
		$authors = $json['author'];
		$image = $json['image']['url'];
		$description = $json['description'];
		$category = $json['articleSection'];
		$title = $json['altHeadline'];
		$authorName = '';
		$counter = 0;
		foreach($authors as $author) {
			$counter++;
			if($counter == count($authors)) {
				$authorName .= $author['name'];
			} else {
				$authorName .= $author['name'] . ', ';
			}
		}
		return array(
			'timestamp' => $timestamp,
			'author' => $authorName,
			'image' => $image,
			'category' => $category,
			'title' => $title
		);
	}

	private function addStory($html, $url) {
		$article_info = $this->getArticleInfo($html);

		$item = array();

		$item['author'] = $article_info['author'];
		$item['timestamp'] = $article_info['timestamp'];
		$item['uri'] = $url;
		$item['content'] = $this->getFullArticle($html);
		$item['title'] = $article_info['title'];
		$item['enclosures'] = array($article_info['image']);
		$item['categories'] = array($article_info['category']);
		$this->items[] = $item;
	}

	private function getFullArticle($html) {

		$content = $html->find('.Article__Content', 0)->innertext;

		foreach(array(
			'<div class="ResponsiveWrapper"'
		) as $element) {
			$content = stripRecursiveHtmlSection($content, 'div', $element);
		}

		return $content;
	}
}
