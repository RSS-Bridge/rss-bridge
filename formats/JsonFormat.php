<?php
/**
 * JsonFormat - JSON Feed Version 1
 * https://jsonfeed.org/version/1
 *
 * Validators:
 * https://validator.jsonfeed.org
 * https://github.com/vigetlabs/json-feed-validator
 */
class JsonFormat extends FormatAbstract {
	const VENDOR_EXCLUDES = array(
		'author',
		'title',
		'uri',
		'timestamp',
		'content',
		'enclosures',
		'categories',
		'uid',
	);

	public function stringify(){
		$urlPrefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$urlHost = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		$urlPath = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '';
		$urlRequest = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

		$extraInfos = $this->getExtraInfos();

		$data = array(
			'version' => 'https://jsonfeed.org/version/1',
			'title' => (!empty($extraInfos['name'])) ? $extraInfos['name'] : $urlHost,
			'home_page_url' => (!empty($extraInfos['uri'])) ? $extraInfos['uri'] : REPOSITORY,
			'feed_url' => $urlPrefix . $urlHost . $urlRequest
		);

		if (!empty($extraInfos['icon'])) {
			$data['icon'] = $extraInfos['icon'];
			$data['favicon'] = $extraInfos['icon'];
		}

		$items = array();
		foreach ($this->getItems() as $item) {
			$entry = array();

			$entryAuthor = $item->getAuthor();
			$entryTitle = $item->getTitle();
			$entryUri = $item->getURI();
			$entryTimestamp = $item->getTimestamp();
			$entryContent = $this->sanitizeHtml($item->getContent());
			$entryEnclosures = $item->getEnclosures();
			$entryCategories = $item->getCategories();

			$vendorFields = $item->toArray();
			foreach (self::VENDOR_EXCLUDES as $key) {
				unset($vendorFields[$key]);
			}

			$entry['id'] = $item->getUid();

			if (empty($entry['id'])) {
				$entry['id'] = $entryUri;
			}

			if (!empty($entryTitle)) {
				$entry['title'] = $entryTitle;
			}
			if (!empty($entryAuthor)) {
				$entry['author'] = array(
					'name' => $entryAuthor
				);
			}
			if (!empty($entryTimestamp)) {
				$entry['date_modified'] = gmdate(DATE_ATOM, $entryTimestamp);
			}
			if (!empty($entryUri)) {
				$entry['url'] = $entryUri;
			}
			if (!empty($entryContent)) {
				if ($this->isHTML($entryContent)) {
					$entry['content_html'] = $entryContent;
				} else {
					$entry['content_text'] = $entryContent;
				}
			}
			if (!empty($entryEnclosures)) {
				$entry['attachments'] = array();
				foreach ($entryEnclosures as $enclosure) {
					$entry['attachments'][] = array(
						'url' => $enclosure,
						'mime_type' => getMimeType($enclosure)
					);
				}
			}
			if (!empty($entryCategories)) {
				$entry['tags'] = array();
				foreach ($entryCategories as $category) {
					$entry['tags'][] = $category;
				}
			}
			if (!empty($vendorFields)) {
				$entry['_rssbridge'] = $vendorFields;
			}

			if (empty($entry['id']))
				$entry['id'] = hash('sha1', $entryTitle . $entryContent);

			$items[] = $entry;
		}
		$data['items'] = $items;

		$toReturn = json_encode($data, JSON_PRETTY_PRINT);

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display(){
		$this
			->setContentType('application/json; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}

	private function isHTML($text) {
		return (strlen(strip_tags($text)) != strlen($text));
	}
}
