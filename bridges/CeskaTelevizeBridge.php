<?php

class CeskaTelevizeBridge extends BridgeAbstract {

	const NAME = 'Česká televize Bridge';
	const URI = 'https://www.ceskatelevize.cz';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'Return newest videos';
	const MAINTAINER = 'kolarcz';

	const PARAMETERS = array(
		array(
			'url' => array(
				'name' => 'url to the show',
				'required' => true,
				'exampleValue' => 'https://www.ceskatelevize.cz/porady/1097181328-udalosti/dily/'
			)
		)
	);

	private function fixChars($text) {
		return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	}

	private function getUploadTimeFromString($string) {
		if (strpos($string, 'dnes') !== false) {
			return strtotime('today');
		} elseif (strpos($string, 'včera') !== false) {
			return strtotime('yesterday');
		} elseif (!preg_match('/(\d+).\s(\d+).(\s(\d+))?/', $string, $match)) {
			returnServerError('Could not get date from Česká televize string');
		}

		$date = sprintf('%04d-%02d-%02d', isset($match[3]) ? $match[3] : date('Y'), $match[2], $match[1]);
		return strtotime($date);
	}

	public function collectData() {
		$url = $this->getInput('url');

		$validUrl = '/^(https:\/\/www\.ceskatelevize\.cz\/porady\/\d+-[a-z0-9-]+\/)(dily\/((nove|vysilani)\/)?)?$/';
		if (!preg_match($validUrl, $url, $match)) {
			returnServerError('Invalid url');
		}

		$category = isset($match[4]) ? $match[4] : 'nove';
		$fixedUrl = "{$match[1]}dily/{$category}/";

		$html = getSimpleHTMLDOM($fixedUrl)
			or returnServerError('Could not request Česká televize');

		$this->feedUri = $fixedUrl;
		$this->feedName = str_replace('Přehled dílů — ', '', $this->fixChars($html->find('title', 0)->plaintext));
		if ($category !== 'nove') {
			$this->feedName .= " ({$category})";
		}

		foreach ($html->find('.episodes-broadcast-content a.episode_list_item') as $element) {
			$itemTitle = $element->find('.episode_list_item-title', 0);
			$itemContent = $element->find('.episode_list_item-desc', 0);
			$itemDate = $element->find('.episode_list_item-date', 0);
			$itemThumbnail = $element->find('img', 0);
			$itemUri = self::URI . $element->getAttribute('href');

			$item = array(
				'title' => $this->fixChars($itemTitle->plaintext),
				'uri' => $itemUri,
				'content' => '<img src="https:' . $itemThumbnail->getAttribute('src') . '" /><br />'
					. $this->fixChars($itemContent->plaintext),
				'timestamp' => $this->getUploadTimeFromString($itemDate->plaintext)
			);

			$this->items[] = $item;
		}
	}

	public function getURI() {
		return isset($this->feedUri) ? $this->feedUri : parent::getURI();
	}

	public function getName() {
		return isset($this->feedName) ? $this->feedName : parent::getName();
	}
}
