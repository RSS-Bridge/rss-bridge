<?php
class MangaDexBridge extends BridgeAbstract {
	const MAINTAINER = 'Yaman Qalieh';
	const NAME = 'MangaDex Bridge';
	const URI = 'https://mangadex.org/';
	const API_ROOT = 'https://api.mangadex.org/';
	const DESCRIPTION = 'Returns MangaDex items using the API';

	const PARAMETERS = array(
		'global' => array(
			'limit' => array(
				'name' => 'Item Limit',
				'type' => 'number',
				'defaultValue' => 10,
				'required' => true
			),
			'lang' => array(
				'name' => 'Chapter Languages',
				'title' => 'comma-separated, two-letter language codes (example "en,jp")',
				'exampleValue' => 'en,jp',
				'required' => false
			),
		),
		'Title Chapters' => array(
			'url' => array(
				'name' => 'URL to title page',
				'exampleValue' => 'https://mangadex.org/title/f9c33607-9180-4ba6-b85c-e4b5faee7192/official-test-manga',
				'required' => true
			),
			'external' => array(
				'name' => 'Allow external feed items',
				'type' => 'checkbox',
				'title' => 'Some chapters are inaccessible or only available on an external site. Include these?'
			)
		)
	);

	const TITLE_REGEX = '#title/(?<uuid>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#';

	protected $feedName = '';
	protected $feedURI = '';

	protected function buildArrayQuery($name, $array) {
		$query = '';
		foreach($array as $item) {
			$query .= '&' . $name . '=' . $item;
		}
		return $query;
	}

	protected function getAPI() {
		$params = array(
			'limit' => $this->getInput('limit')
		);

		$array_params = array();
		if (!empty($this->getInput('lang'))) {
			$array_params['translatedLanguage[]'] = explode(',', $this->getInput('lang'));
		}

		switch($this->queriedContext) {
		case 'Title Chapters':
			preg_match(self::TITLE_REGEX, $this->getInput('url'), $matches)
				or returnClientError('Invalid URL Parameter');
			$this->feedURI = self::URI . 'title/' . $matches['uuid'];
			$params['order[updatedAt]'] = 'desc';
			if (!$this->getInput('external')) {
				$params['includeFutureUpdates'] = '0';
			}
			$array_params['includes[]'] = array('manga', 'scanlation_group', 'user');
			$uri = self::API_ROOT . 'manga/' . $matches['uuid'] . '/feed';
			break;
		default:
			returnServerError('Unimplemented Context (getAPI)');
		}

		$uri .= '?' . http_build_query($params);

		// Arrays are passed as repeated keys to MangaDex
		// This cannot be handled by http_build_query
		foreach($array_params as $name => $array_param) {
			$uri .= $this->buildArrayQuery($name, $array_param);
		}

		return $uri;

	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Title Chapters':
			return $this->feedName . ' Chapters';
		default:
			return parent::getName();
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
		case 'Title Chapters':
			return $this->feedURI;
		default:
			return parent::getURI();
		}
	}

	public function collectData() {
		$api_uri = $this->getApi();
		$header = array(
			'Content-Type: application/json'
		);
		$content = json_decode(getContents($api_uri, $header), true);
		if ($content['result'] == 'ok') {
			$content = $content['data'];
		} else {
			returnServerError('Could not retrieve API results');
		}

		switch($this->queriedContext) {
		case 'Title Chapters':
			$this->getChapters($content);
			break;
		default:
			returnServerError('Unimplemented Context (collectData)');
		}
	}

	protected function getChapters($content) {
		foreach($content as $chapter) {
			$item = array();
			$item['uid'] = $chapter['id'];
			$item['uri'] = self::URI . 'chapter/' . $chapter['id'];

			// Preceding space accounts for Manga title added later
			$item['title'] = ' Chapter ' . $chapter['attributes']['chapter'];
			if (!empty($chapter['attributes']['title'])) {
				$item['title'] .= ' - ' . $chapter['attributes']['title'];
			}
			$item['title'] .= ' [' . $chapter['attributes']['translatedLanguage'] . ']';

			$item['timestamp'] = $chapter['attributes']['updatedAt'];

			$groups = array();
			$users = array();
			foreach($chapter['relationships'] as $rel) {
				switch($rel['type']) {
				case 'scanlation_group':
					$groups[] = $rel['attributes']['name'];
					break;
				case 'manga':
					if (empty($this->feedName)) {
						$this->feedName = reset($rel['attributes']['title']);
					}
					$item['title'] = reset($rel['attributes']['title']) . $item['title'];
					break;
				case 'user':
					if (isset($item['author'])) {
						$users[] = $rel['attributes']['username'];
					} else {
						$item['author'] = $rel['attributes']['username'];
					}
					break;
				}
			}
			$item['content'] = 'Groups: ' .
							 (empty($groups) ? 'No Group' : implode(', ', $groups));
			if (!empty($users)) {
				$item['content'] .= '<br>Other Users: ' . implode(', ', $users);
			}

			$this->items[] = $item;
		}
	}
}
