<?php
class SteamCommunityBridge extends BridgeAbstract {
	const NAME = 'Steam Community';
	const URI = 'https://www.steamcommunity.com';
	const DESCRIPTION = 'Get the latest community updates for a game on Steam.';
	const MAINTAINER = 'thefranke';
	const CACHE_TIMEOUT = 3600; // 1h

	const PARAMETERS = array(
		array(
			'i' => array(
				'name' => 'App ID',
				'required' => true
			),
			'category' => array(
				'name' => 'category',
				'type' => 'list',
				'exampleValue' => 'Artwork',
				'title' => 'Select a category',
				'values' => array(
					'Artwork' => 'images',
					'Screenshots' => 'screenshots',
					'Videos' => 'videos'
				)
			)
		)
	);

	public function getIcon() {
		return self::URI . '/favicon.ico';
	}

	protected function getMainPage() {
		$category = $this->getInput('category');
		$html = getSimpleHTMLDOM($this->getURI() . '/?p=1&browsefilter=mostrecent')
			or returnServerError('Could not fetch Steam data.');

		return $html;
	}

	public function getName() {
		$category = $this->getInput('category');

		if (is_null('i') || is_null($category)) {
			return self::NAME;
		}

		$html = $this->getMainPage();

		$titleItem = $html->find('div.apphub_AppName', 0);

		if (!$titleItem)
			return self::NAME;

		return $titleItem->innertext . ' (' . ucwords($category) . ')';
	}

	public function getURI() {
		return self::URI . '/app/'
			. $this->getInput('i') . '/'
			. $this->getInput('category');
	}

	public function collectData() {
		$category = $this->getInput('category');
		$html = $this->getMainPage();
		$cards = $html->find('div.apphub_Card');

		foreach($cards as $card) {
			$uri = $card->getAttribute('data-modal-content-url');

			$htmlCard = getSimpleHTMLDOMCached($uri);

			$author = $card->find('div.apphub_CardContentAuthorName', 0)->innertext;
			$author = strip_tags($author);

			$title = $author . '\'s screenshot';

			if ($category != 'screenshots')
				$title = $htmlCard->find('div.workshopItemTitle', 0)->innertext;

			$date = $htmlCard->find('div.detailsStatRight', 0)->innertext;

			// create item
			$item = array();
			$item['title'] = $title;
			$item['uri'] = $uri;
			$item['timestamp'] = strtotime($date);
			$item['author'] = $author;
			$item['categories'] = $category;

			$media = $htmlCard->getElementById('ActualMedia');
			$mediaURI = $media->getAttribute('src');
			$downloadURI = $mediaURI;

			if ($category == 'videos') {
				preg_match('/.*\/embed\/(.*)\?/', $mediaURI, $result);
				$youtubeID = $result[1];
				$mediaURI = 'https://img.youtube.com/vi/' . $youtubeID . '/hqdefault.jpg';
				$downloadURI = 'https://www.youtube.com/watch?v=' . $youtubeID;
			}

			$desc = "";

			if ($category == 'screenshots') {
				$descItem = $htmlCard->find('div.screenshotDescription', 0);
				if ($descItem)
					$desc = $descItem->innertext;
			}

			if ($category == 'images') {
				$descItem = $htmlCard->find('div.nonScreenshotDescription', 0);
				if ($descItem)
					$desc = $descItem->innertext;
				$downloadURI = $htmlCard->find('a.downloadImage', 0)->href;
			}

			$item['content'] = '<p><a href="' . $downloadURI . '"><img src="' . $mediaURI . '"/></a></p>';
			$item['content'] .= '<p>' . $desc . '</p>';

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}
}
