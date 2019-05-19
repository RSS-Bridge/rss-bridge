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

	public function getName() {
		return self::NAME . ': ' . ucwords($this->getInput('category'));
	}

	public function getURI() {
		return self::URI . '/app/'
			. $this->getInput('i') . '/'
			. $this->getInput('category');
	}

	public function collectData() {
		$category = $this->getInput('category');

		$html = getSimpleHTMLDOM($this->getURI() . '/?p=1&browsefilter=mostrecent')
			or returnServerError('Could not fetch Steam data.');

		$cards = $html->find('div.apphub_Card');

		foreach($cards as $card) {
			$uri = $card->getAttribute('data-modal-content-url');

			$htmlCard = getSimpleHTMLDOMCached($uri);

			$author = $card->find('div.apphub_CardContentAuthorName', 0)->innertext;
			$title = $author . '\'s screenshot';

			if ($category != 'screenshots')
				$title = $htmlCard->find('div.workshopItemTitle', 0)->innertext;

			$date = $htmlCard->find('div.detailsStatRight', 0)->innertext;

			// create item
			$item = array();
			$item['title'] = $title;
			$item['uri'] = $uri;
			$item['timestamp'] = strtotime($date);
			$item['author'] = strip_tags($author);
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

			$item['content'] = '<p><a href="' . $downloadURI . '">'
				. '<img src="' . $mediaURI . '"/></a></p>';

			if ($category == 'images') {
				$desc = $htmlCard->find('div.nonScreenshotDescription', 0)->innertext;
				$item['content'] .= '<p>' . $desc . '</p>';
				$downloadURI = $htmlCard->find('a.downloadImage', 0)->href;
			}

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}
}
