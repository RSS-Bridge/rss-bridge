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

	private $jsonRegex = '/window\.__PRELOADED_STATE__ = ((?:.*)});/';

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI());

		$results = $html->find('div.results', 0);

		foreach($results->find('li.col-6.col-sm-4.col-md-3.col-lg-2.px-2.pb-4') as $li) {
			$item = array();

			$videoPath = self::URI . $li->children(0)->href;
			$videoPageHtml = getSimpleHTMLDOMCached($videoPath, 3600);

			$json = $this->extractJson($videoPageHtml);
			$id = array_keys((array) $json->media->index)[0];

			$item['uri'] = $videoPath;
			$item['title'] = $json->media->index->$id->title;
			$item['timestamp'] = $json->media->index->$id->published_at;
			$item['enclosures'][] = $json->media->index->$id->media->thumbnail;

			$description = $videoPageHtml->find('div.description', 0);
			if ($videoPageHtml->find('div.core-see-more', 0)) {
				$description .= $videoPageHtml->find('div.core-see-more', 0);
			}

			$item['content'] = <<<EOD
			<video controls poster="{$json->media->index->$id->media->thumbnail}" preload="none">
				<source src="{$json->media->index->$id->media->mp4_url}" type="video/mp4">
			</video>
			<p>{$description}</p>
EOD;

			$this->items[] = $item;

			if (count($this->items) >= 10) {
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

	/**
	 * Extract JSON from page
	 */
   private function extractJson($html) {

		if (!preg_match($this->jsonRegex, $html, $parts)) {
			returnServerError('Failed to extract data from page');
		}

		$data = json_decode($parts[1]);

		if ($data === false) {
			returnServerError('Failed to decode extracted data');
		}

		return $data;
	}
}
