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
					'Health' => 'health',
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
						'Spain' => 'es',
						'India' => 'in',
						'Mexico' => 'mx',
				),
				'defaultValue' => 'us',
			)
		)
	);

	const CACHE_TIMEOUT = 1800; // 30 mins

	private $videoId = '';
	private $videoType = '';
	private $videoImage = '';

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$results = $html->find('div.results', 0);

		foreach($results->find('li.col-6.col-sm-4.col-md-3.col-lg-2.px-2.pb-4') as $index => $li) {
			$item = array();

			$videoPath = self::URI . $li->children(0)->href;

			$videoPageHtml = getSimpleHTMLDOMCached($videoPath, 3600)
				or returnServerError('Could not request: ' . $videoPath);

			$this->videoImage = $videoPageHtml->find('meta[name="twitter:image"]', 0)->content;

			$this->processTwitterImage();

			$description = $videoPageHtml->find('div.description', 0);

			$item['uri'] = $videoPath;
			$item['title'] = $description->find('h1', 0)->plaintext;

			if ($description->find('div.date', 0)->children(0)) {
				$description->find('div.date', 0)->children(0)->outertext = '';
			}

			$item['content'] = $this->processContent(
				$description
			);

			$item['timestamp'] = $this->processDate($description);
			$item['enclosures'][] = $this->videoImage;

			$this->items[] = $item;

			if (count($this->items) >= 5) {
				break;
			}
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
			return self::URI . '/' . $this->getInput('edition') . '/' . $this->getInput('category');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
			$parameters = $this->getParameters();

			$editionValues = array_flip($parameters[0]['edition']['values']);
			$categoryValues = array_flip($parameters[0]['category']['values']);

			return $categoryValues[$this->getInput('category')] . ' - ' .
				$editionValues[$this->getInput('edition')] . ' - Brut.';
		}

		return parent::getName();
	}

	private function processDate($description) {

		if ($this->getInput('edition') === 'uk') {
			$date = DateTime::createFromFormat('d/m/Y H:i', $description->find('div.date', 0)->innertext);
			return strtotime($date->format('Y-m-d H:i:s'));
		}

		return strtotime($description->find('div.date', 0)->innertext);
	}

	private function processContent($description) {

		$content = '<video controls poster="' . $this->videoImage . '" preload="none">
			<source src="https://content.brut.media/video/' . $this->videoId . '-' . $this->videoType . '-web.mp4"
            type="video/mp4">
			</video>';
		$content .= '<p>' . $description->find('h2.mb-1', 0)->innertext . '</p>';

		if ($description->find('div.text.pb-3', 0)->children(1)->class != 'date') {
			$content .= '<p>' . $description->find('div.text.pb-3', 0)->children(1)->innertext . '</p>';
		}

		return $content;
	}

	private function processTwitterImage() {
		/**
		 * Extract video ID + type from twitter image
		 *
		 * Example (wrapped):
		 *  https://img.brut.media/thumbnail/
		 *  the-life-of-rita-moreno-2cce75b5-d448-44d2-a97c-ca50d6470dd4-square.jpg
		 *  ?ts=1559337892
		 */
		$fpath = parse_url($this->videoImage, PHP_URL_PATH);
		$fname = basename($fpath);
		$fname = substr($fname, 0, strrpos($fname, '.'));
		$parts = explode('-', $fname);

		if (end($parts) === 'auto') {
			$key = array_search('auto', $parts);
			unset($parts[$key]);
		}

		$this->videoId = implode('-', array_splice($parts, -6, 5));
		$this->videoType = end($parts);
	}
}
