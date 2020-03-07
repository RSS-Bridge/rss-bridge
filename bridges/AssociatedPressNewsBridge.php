<?php
class AssociatedPressNewsBridge extends BridgeAbstract {
	const NAME = 'Associated Press News Bridge';
	const URI = 'https://apnews.com/';
	const DESCRIPTION = 'Returns newest articles by topic';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Standard Topics' => array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'list',
				'values' => array(
					'AP Top News' => 'apf-topnews',
					'Sports' => 'apf-sports',
					'Entertainment' => 'apf-entertainment',
					'Oddities' => 'apf-oddities',
					'Travel' => 'apf-Travel',
					'Technology' => 'apf-technology',
					'Lifestyle' => 'apf-lifestyle',
					'Business' => 'apf-business',
					'U.S. News' => 'apf-usnews',
					'Health' => 'apf-Health',
					'Science' => 'apf-science',
					'International News' => 'apf-intlnews',
					'Politics' => 'apf-politics',
					'Religion' => 'apf-religion',
					'Photo Galleries' => 'PhotoGalleries',
					'Fact Checks' => 'APFactCheck',
				),
				'defaultValue' => 'apf-topnews',
			),
		),
		'Custom Topic' => array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'Election2020'
			),
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $tagEndpoint = 'https://afs-prod.appspot.com/api/v2/feed/tag?tags=';
	private $feedName = '';

	public function collectData() {
		$json = getContents($this->getTagURI())
			or returnServerError('Could not request: ' . $this->getTagURI());

		$tagContents = json_decode($json, true);

		if (empty($tagContents['tagObjs'])) {
			returnClientError('Topic not found: ' . $this->getInput('topic'));
		}

		if ($this->getInput('topic') === 'Podcasts') {
			returnClientError('Podcasts topic feed is not supported');
		}

		$this->feedName = $tagContents['tagObjs'][0]['name'];

		foreach ($tagContents['cards'] as $index => $card) {
			$item = array();

			$json = getContents($card['contents'][0]['gcsUrl'])
				or returnServerError('Could not request: ' . $card['contents'][0]['gcsUrl']);

			$storyContent = json_decode($json, true);
			$html = $storyContent['storyHTML'];

			switch($storyContent['contentType']) {
				case 'web': // Skip link only content
					continue 2;

				case 'video':
					$html = $this->processVideo($storyContent);

					$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/'
						. $storyContent['media'][0]['id'] . '/800.jpeg';
					break;
				default:
					if (empty($storyContent['storyHTML'])) {
						continue 2;
					}

					$html = defaultLinkTo($html, self::URI);
					$html = str_get_html($html);

					$this->processMediaPlaceholders($html, $storyContent);
					$this->processHubLinks($html, $storyContent);
					$this->processIframes($html);
					
					$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/'
						. $storyContent['leadPhotoId'] . '/800.jpeg';
			}

			$item['title'] = $card['contents'][0]['headline'];
			$item['uri'] = self::URI . $card['contents'][0]['shortId'];
			$item['timestamp'] = $storyContent['published'];

			if (substr($storyContent['bylines'], 0, 2) == 'By') {
				$item['author'] = ltrim($storyContent['bylines'], 'By ');
			} else {
				$item['author'] = $storyContent['bylines'];
			}

			$item['content'] = $html;

			foreach ($storyContent['tagObjs'] as $tag) {
				$item['categories'][] = $tag['name'];
			}

			$this->items[] = $item;

			if (count($this->items) >= 5) {
				break;
			}
		}
	}

	public function getURI() {
		if (!is_null($this->getInput('topic'))) {
			return self::URI . $this->getInput('topic');
		}

		return parent::getURI();
	}

	public function getName() {
		if (!empty($this->feedName)) {
			return $this->feedName . ' - Associated Press';
		}

		return parent::getName();
	}

	private function getTagURI() {
		if (!is_null($this->getInput('topic'))) {
			return $this->tagEndpoint . $this->getInput('topic');
		}

		return parent::getURI();
	}

	private function processMediaPlaceholders($html, $storyContent) {

		foreach ($html->find('div.media-placeholder') as $div) {
			$key = array_search($div->id, $storyContent['mediumIds']);
			$media = $storyContent['media'][$key];
			
			if ($media['type'] === 'Photo') {

				$mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
				$mediaCaption = $media['caption'];

				$div->outertext = <<<EOD
<figure><img loading="lazy" src="{$mediaUrl}"/><figcaption>{$mediaCaption}</figcaption></figure>
EOD;
			}

			if ($media['type'] === 'YouTube') {
				$div->outertext = <<<EOD
<iframe src="https://www.youtube.com/embed/{$media['externalId']}" width="560" height="315">
</iframe>
EOD;
			}
		}
	}

	private function processHubLinks($html, $storyContent) {

		if (!empty($storyContent['richEmbeds'])) {
			foreach ($storyContent['richEmbeds'] as $embed) {

				if ($embed['type'] === 'Hub Link') {
					$url = self::URI . $embed['tag']['id'];
					$div = $html->find('div[id=' . $embed['id'] . ']', 0);
					$div->outertext = <<<EOD
<p><a href="{$url}">{$embed['calloutText']} {$embed['displayName']}</a></p>
EOD;
				}
			}
		}
	}

	private function processVideo($storyContent) {
		$video = $storyContent['media'][0];

		if ($video['type'] === 'YouTube') {
			$html = <<<EOD
<iframe width="560" height="315" src="https://www.youtube.com/embed/{$video['externalId']}" frameborder="0" allowfullscreen></iframe>
EOD;
		} else {
			$html = <<<EOD
<video controls poster="https://storage.googleapis.com/afs-prod/media/{$video['id']}/800.jpeg" preload="none">
	<source src="{$video['gcsBaseUrl']} {$video['videoRenderedSizes'][0]} {$video['videoFileExtension']}" type="video/mp4">
</video>
EOD;
		}
		
		return $html;
	}

	// Remove datawrapper.dwcdn.net iframes and related javaScript
	private function processIframes($html) {

		foreach ($html->find('iframe') as $index => $iframe) {
			if (preg_match('/datawrapper\.dwcdn\.net/', $iframe->src)) {
				$iframe->outertext = '';

				if ($html->find('script', $index)) {
					$html->find('script', $index)->outertext = '';
				}
			}
		}
	}
}
