<?php
class RoadAndTrackBridge extends BridgeAbstract {
	const MAINTAINER = 'teromene';
	const NAME = 'Road And Track Bridge';
	const URI = 'https://www.roadandtrack.com/';
	const CACHE_TIMEOUT = 86400; // 24h
	const DESCRIPTION = 'Returns the latest news from Road & Track.';

	const PARAMETERS = array(
		array(
			'new-cars' => array(
				'name' => 'New Cars',
				'type' => 'checkbox',
				'exampleValue' => 'checked',
				'title' => 'Activate to load New Cars articles'
			),
			'motorsports' => array(
				'name' => 'Motorsports',
				'type' => 'checkbox',
				'exampleValue' => 'checked',
				'title' => 'Activate to load Motorsports articles'
			),
			'car-culture' => array(
				'name' => 'Car Culture',
				'type' => 'checkbox',
				'exampleValue' => 'checked',
				'title' => 'Activate to load Car Culture articles'
			),
			'car-shows' => array(
				'name' => 'Car shows',
				'type' => 'checkbox',
				'exampleValue' => 'checked',
				'title' => 'Activate to load Car shows articles'
			)
		)
	);

	const API_TOKEN = '2e18e904-d9cd-4911-b30c-1817b1e0b04b';
	const SIG_URL = 'https://cloud.mazdigital.com/feeds/production/comboapp/204/api/v3/';
	const GSIG_URL = 'https://dashboard.mazsystems.com/services/cf_access?app_id=204&app_type=comboapp&api_token=';

	public function collectData() {

		$signVal = json_decode(getContents(self::GSIG_URL . self::API_TOKEN));
		$signVal = $signVal->signature;

		$newsElements = array();
		if($this->getInput('new-cars')) {
			$newsElements = array_merge($newsElements,
										json_decode(getContents(self::SIG_URL . '7591/item_feed' . $signVal))
							);
		}
		if($this->getInput('motorsports')) {
			$newsElements = array_merge($newsElements,
										json_decode(getContents(self::SIG_URL . '7590/item_feed' . $signVal))
							);
		}
		if($this->getInput('car-culture')) {
			$newsElements = array_merge($newsElements,
										json_decode(getContents(self::SIG_URL . '7588/item_feed' . $signVal))
							);
		}
		if($this->getInput('car-shows')) {
			$newsElements = array_merge($newsElements,
										json_decode(getContents(self::SIG_URL . '7589/item_feed' . $signVal))
							);
		}

		usort($newsElements, function($a, $b) {
			return $b->published - $a->published;
		});

		$limit = 19;
		foreach($newsElements as $element) {

			$item = array();
			$item['uri'] = $element->sourceUrl;
			$item['timestamp'] = $element->published;
			$item['enclosures'] = array($element->cover->url);
			$item['title'] = $element->title;
			$item['content'] = $this->getArticleContent($element);
			$this->items[] = $item;

			if($limit > 0) {
				$limit--;
			} else {
				break;
			}

		}

	}

	private function getArticleContent($article) {

		return getContents($article->contentUrl);

	}
}
