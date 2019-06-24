<?php
class InternetArchiveBridge extends BridgeAbstract {
	const NAME = 'Internet Archive Bridge';
	const URI = 'https://archive.org';
	const DESCRIPTION = 'Returns newest uploads, posts and more from an account';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Account' => array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'exampleValue' => '@verifiedjoseph',
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'Uploads' => 'uploads',
					'Posts' => 'posts',
					'Reviews' => 'reviews',
					'collections' => 'collections',
					'Web Archives' => 'web-archive',
				),
				'defaultValue' => 'uploads',
			)
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $feedName = '';

	private $skipClasses = array(
		'item-ia mobile-header hidden-tiles',
		'item-ia account-ia'
	);
	
	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		if ($this->getInput('content') === 'uploads' || $this->getInput('content') === 'collections') {

			$detailsDivNumber = 0;

			foreach ($html->find('div.results > div[data-id]') as $index => $result) {
				$item = array();
				
				if (in_array($result->class, $this->skipClasses)) {
					continue;
				}

				if ($result->class === 'item-ia') {
					$item = $this->processUpload($result);
				}

				if ($result->class === 'item-ia collection-ia') {
					$item = $this->processCollection($result);
				}

				$hiddenDetails = $this->processHiddenDetails($html, $detailsDivNumber, $item);

				$this->items[] = array_merge($item, $hiddenDetails);

				$detailsDivNumber++;
			}
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('username')) && !is_null($this->getInput('content'))) {
			return self::URI . '/details/' . $this->processUsername() . '&tab=' . $this->getInput('content');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - Internet Archive';
		}

		return parent::getName();
	}

	private function processUsername() {

		if (substr($this->getInput('username'), 0, 1) != '@') {
			return '@' . $this->getInput('username');
		}

		return $this->getInput('username');
	}
	
	private function processUpload($result) {

		$item = array();
		
		$collection = $result->find('a.stealth', 0);
		$collectionLink = self::URI . $collection->href;
		$collectionTitle = $collection->find('div.item-parent-ttl', 0)->innertext;
		
		$item['title'] = trim($result->find('div.ttl', 0)->innertext);
		$item['timestamp'] = strtotime($result->find('div.hidden-tiles.pubdate.C.C3', 0)->children(0)->innertext);
		$item['uri'] = self::URI . $result->find('div.item-ttl.C.C2 > a' , 0)->href;
		
		if ($result->find('div.by.C.C4', 0)->children(2)) {
			$item['author'] = $result->find('div.by.C.C4', 0)->children(2)->plaintext;
		}
		
		$item['content'] = <<<EOD
<p>Media Type: {$result->attr['data-mediatype']}<br>
Collection: <a href="{$collectionLink}">{$collectionTitle}</a></p>
EOD;


		//$item['enclosures'];
		
		return $item;
	}	
	
	private function processCollection($result) {

		$item = array();
		
		$item['content'] = '';
		
		return $item;
	}
	
	private function processHiddenDetails($html, $detailsDivNumber, $item) {

		$description = '';

		if ($html->find('div.details-ia.hidden-tiles', $detailsDivNumber)) {
			$detailsDiv = $html->find('div.details-ia.hidden-tiles', $detailsDivNumber);

			if ($detailsDiv->find('div.C234', 0)->children(0)) {
				$description = $detailsDiv->find('div.C234', 0)->children(0)->plaintext;
				
				$detailsDiv->find('div.C234', 0)->children(0)->innertext = '';
			}

			$topics = trim($detailsDiv->find('div.C234', 0)->plaintext);
			
			if (!empty($topics)) {
				$topics = trim($detailsDiv->find('div.C234', 0)->plaintext);
				$topics = trim(substr($topics, 7));

				$item['categories'] = explode(',', $topics);	
			}

			$item['content'] = '<p>' . $description . '</p>' . $item['content'];
		}

		return $item;
	}
}
