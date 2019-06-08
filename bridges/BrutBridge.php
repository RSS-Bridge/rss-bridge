<?php
class BrutBridge extends BridgeAbstract {
	const NAME = 'Brut Bridge';
	const URI = 'https://www.brut.media';
	const DESCRIPTION = 'Returns 5 newest videos by category and edition';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'category' => array(
				'name' => 'Category',
				'type' => 'list',
				'values' => array(
					'News' => 'news',
					'International' => 'international',
					'Economy' => 'economy',
					'Science and Technology' => 'science-and-technology',
					'Entertainment' => 'entertainment',
					'Sports' => 'sport',
					'Nature' => 'nature',
				),
				'defaultValue' => 'news',
			),
			'edition' => array(
				'name' => ' Edition',
				'type' => 'list',
					'values' => array(
						'United States' => 'us',
						'United Kingdom' => 'uk',
						'France' => 'fr',
						'India' => 'in',
						'Mexico' => 'mx',
				),
				'defaultValue' => 'us',
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30 mins

	private $videoImageRegex = '/https:\/\/img\.brut\.media\/thumbnail\/(?:[a-z0-9-]+)-([a-z0-9]+-[a-z0-9]+-[a-z0-9]+-[a-z0-9]+-[a-z0-9]+)-(square|landscape|portrait)(?:-auto)?\.jpg/';

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$results = $html->find('div.results', 0);

		foreach($results->find('li.col-6.col-sm-4.col-md-3.col-lg-2.px-2.pb-4') as $index => $li) {
			$item = array();

			if ($index > 5) {
				break;
			}

			$videoPath = self::URI . $li->children(0)->href;

			$videoPageHtml = getSimpleHTMLDOMCached($videoPath, 3600)
				or returnServerError('Could not request: ' . $videoPath);

			$videoImage = $videoPageHtml->find("meta[name=twitter:image]")[0]->content;
			
			preg_match($this->videoImageRegex, $videoImage, $matches)
				or returnServerError('Could not extract video id and type from image url: ' . $videoImage);

			$videoId = $matches[1];
			$videoType = $matches[2];

			$description = $videoPageHtml->find('div.description', 0);

			$item['uri'] = $videoPath;
			$item['title'] = $description->find('h1', 0)->plaintext;

			if ($description->find('div.date', 0)->children(0)) {
				$description->find('div.date', 0)->children(0)->outertext = '';
			}

			$item['content'] = $this->processContent(
				$description, 
				$videoId,
				$videoType,
				$videoImage
			);

			$item['timestamp'] = $this->processDate($description);
			$item['enclosures'][] = $videoImage;

			$this->items[] = $item;
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
			return self::URI . '/' . $this->getInput('edition') . '/' . $this->getInput('category');
		}

		return parent::getURI();
	}

	private function processDate($description) {

		if ($this->getInput('edition') === 'uk') {
			$date = DateTime::createFromFormat('d/m/Y H:i', $description->find('div.date', 0)->innertext);
			return strtotime($date->format('Y-m-d H:i:s'));
		}

		return strtotime($description->find('div.date', 0)->innertext);
	}

	private function processContent($description, $videoId, $videoType, $videoImage) {

		$content = '<video controls poster="' . $videoImage . '" preload="none">
			<source src="https://content.brut.media/video/' . $videoId . '-' . $videoType . '-web.mp4"
            type="video/mp4">
			</video>';
		$content .= '<p>' . $description->find('h2.mb-1', 0)->innertext . '</p>';

		if ($description->find('div.text.pb-3', 0)->children(1)->class != 'date') {
			$content .= '<p>' . $description->find('div.text.pb-3', 0)->children(1)->innertext . '</p>';
		}

		return $content;
	}
}
