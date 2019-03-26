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

	const SIG_URL = 'https://cloud.mazdigital.com/feeds/production/comboapp/204/api/v3/';

	public function collectData() {

		//Magic
		$signVal  = '?Policy=eyJTdGF0ZW1lbnQiOlt7IlJlc291cmNlIjoiaHR0cHM6Ly9jbG91ZC5tYXpkaWd';
		$signVal .= 'pdGFsLmNvbS9mZWVkcy9wcm9kdWN0aW9uL2NvbWJvYXBwLzIwNC8qIiwiQ29uZGl0aW9uIj';
		$signVal .= 'p7IkRhdGVMZXNzVGhhbiI6eyJBV1M6RXBvY2hUaW1lIjoxNTUyNTU5MDUzfSwiSXBBZGRyZ';
		$signVal .= 'XNzIjp7IkFXUzpTb3VyY2VJcCI6IjAuMC4wLjAvMCJ9fX1dfQ__&Signature=jgS~Jccjs';
		$signVal .= 'lXMMywWesmwDpUbHvEmrADRP7iBRzT~OiP-O~zI-8TtQzqTP7GUrpB9~v69CvhO7-JVtw94';
		$signVal .= 'VC3N6lQrwsxTTIhpS57YGeV~MbZx~P653yUV7jb3jpJE2yUawfXnEkD-XzOIn8-caMo~14i';
		$signVal .= 'KuWV9KNDkTJaRgOMy0rrVpWqiuBjCu5s5B8Ylt2qwcpOvHjXSqG9IY5c7GUIXKsk8yXzGFi';
		$signVal .= 'yzy8hfuGgdx0n7fgl7c4-EoDgQaz~U76g0epejPxV5Csj16rCCfAqBU5kZJnACZ1vvOvRcV';
		$signVal .= 'Wiu8KUuUuCS04SPmJ73Y5XoY8~uXRScxZG1kAFTIAhT4nYVlg__&Key-Pair-Id=APKAIZB';
		$signVal .= 'QNNSW4WGIFP4Q';

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
