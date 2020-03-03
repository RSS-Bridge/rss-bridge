<?php
class AssociatedPressNewsBridge extends BridgeAbstract {
	const NAME = 'Associated Press News Bridge';
	const URI = 'https://apnews.com/';
	const DESCRIPTION = 'Returns newest articles by topic';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'By Topic' => array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'list',
				'values' => array(
					'AP Top News' => 'apf-topnews',
					'Sports' => 'apf-sports',
				),
				'defaultValue' => 'apf-topnews',
			),
		),
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $tagEndpoint = 'https://afs-prod.appspot.com/api/v2/feed/tag?tags=';

	public function collectData() {
		$json = getContents($this->getTagURI())
			or returnServerError('Could not request: ' . $this->getTagURI());

		$tagContents = json_decode($json, true);

		foreach ($tagContents['cards'] as $index => $card) {
			$item = array();

			echo $card['contents'][0]['gcsUrl'];
			
			$json = getContents($card['contents'][0]['gcsUrl'])
				or returnServerError('Could not request: ' . $card['contents'][0]['gcsUrl']);

			$storyContent = json_decode($json, true);

			$html = defaultLinkTo($storyContent['storyHTML'], self::URI);
			$html = str_get_html($html);

			foreach ($html->find('div.media-placeholder') as $div) {
				$key = array_search($div->id, $storyContent['mediumIds']);

				if (isset($storyContent['media'][$key]) && $storyContent['media'][$key]['type'] === 'Photo') {
					$media = $storyContent['media'][$key];

					$mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
					$mediaCaption = $media['caption'];

					$div->innertext = '<figure><img loading="lazy" src="' . $mediaUrl . '"/>
						<figcaption>' . $mediaCaption . '</figcaption>
						</figure>';
				}
			}

			$item['uri'] = self::URI . $card['contents'][0]['shortId'];
			$item['title'] = $card['contents'][0]['headline'];
			$item['content'] = $html;
			$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/' . $storyContent['leadPhotoId'] . '/800.jpeg';

			$this->items[] = $item;

			if (count($this->items) >= 2) {
				break;
			}
		}
	}

	public function getURI() {
		return parent::getURI();
	}
	
	public function getName() {

		/*if ($this->queriedContext === 'Latest articles') {
			return $this->queriedContext . ' - Bandcamp Daily';
		}

		if (!is_null($this->getInput('content'))) {
			$contentValues = array_flip(self::PARAMETERS[$this->queriedContext]['content']['values']);

			return $contentValues[$this->getInput('content')] . ' - Bandcamp Daily';
		}*/

		return parent::getName();
	}
	
	private function getTagURI() {

		switch($this->queriedContext) {
			case 'By Topic':	
				return $this->tagEndpoint . $this->getInput('topic');
			case 'Franchises':
				return self::URI . '/' . $this->getInput('content');
			case 'Genres':
				return self::URI . '/' . $this->getInput('content');
		}

		return parent::getURI();
	}
	
	private function processMediaPlaceholders($html, $storyContent) {
	
		foreach ($html->find('div.media-placeholder') as $div) {
			$key = array_search($div->id, $storyContent['mediumIds']);

			if ($storyContent['media'][$key]['type'] === 'Photo') {
				$media = $storyContent['media'][$key];

				$mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
				$mediaCaption = $media['caption'];

				$div->innertext = <<<EOD
<figure><img loading="lazy" src="{$mediaUrl}"/><figcaption>{$mediaCaption}</figcaption></figure>
EOD;
			} else if ($storyContent['media'][$key]['type'] === 'YouTube') {
				$div->innertext = <<<EOD
					<iframe allowfullscreen="1" src="https://www.youtube.com/embed/{$storyContent['media'][$key]['externalId']}" width="560" height="315"></iframe>
EOD;
			}
		}
	}
}
