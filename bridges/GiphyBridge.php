<?php

class GiphyBridge extends BridgeAbstract {

	const MAINTAINER = 'dvikan';
	const NAME = 'Giphy Bridge';
	const URI = 'https://giphy.com/';
	const CACHE_TIMEOUT = 60 * 60 * 8; // 8h
	const DESCRIPTION = 'Bridge for giphy.com';

	const PARAMETERS = array( array(
		's' => array(
			'name' => 'search tag',
			'exampleValue' => 'bird',
			'required' => true
		),
		'noGif' => array(
			'name' => 'Without gifs',
			'type' => 'checkbox',
			'title' => 'Exclude gifs from the results'
		),
		'noStick' => array(
			'name' => 'Without stickers',
			'type' => 'checkbox',
			'title' => 'Exclude stickers from the results'
		),
		'n' => array(
			'name' => 'max number of returned items (max 50)',
			'type' => 'number',
			'exampleValue' => 3,
		)
	));

	protected function getGiphyItems($entries){
		foreach($entries as $entry) {
			$createdAt = new \DateTime($entry->import_datetime);

			$this->items[] = array(
				'id'		=> $entry->id,
				'uri'		=> $entry->url,
				'author'	=> $entry->username,
				'timestamp'	=> $createdAt->format('U'),
				'title'		=> $entry->title,
				'content'	=> <<<HTML
<a href="{$entry->url}">
<img
	loading="lazy"
	src="{$entry->images->downsized->url}"
	width="{$entry->images->downsized->width}"
	height="{$entry->images->downsized->height}" />
</a>
HTML
			);
		}
	}

	public function collectData() {
		/**
		 * This uses a public beta key which has severe rate limiting.
		 *
		 * https://giphy.api-docs.io/1.0/welcome/access-and-api-keys
		 * https://giphy.api-docs.io/1.0/gifs/search-1
		 */
		$apiKey = 'dc6zaTOxFJmzC';
		$limit = min($this->getInput('n') ?: 10, 50);
		$endpoints = array();
		if (empty($this->getInput('noGif'))) {
			$endpoints[] = 'gifs';
		}
		if (empty($this->getInput('noStick'))) {
			$endpoints[] = 'stickers';
		}

		foreach ($endpoints as $endpoint) {
			$uri = sprintf(
				'https://api.giphy.com/v1/%s/search?q=%s&limit=%s&api_key=%s',
				$endpoint,
				rawurlencode($this->getInput('s')),
				$limit,
				$apiKey
			);

			$result = json_decode(getContents($uri));

			$this->getGiphyItems($result->data);
		}

		usort($this->items, function ($a, $b) {
			return $a['timestamp'] < $b['timestamp'];
		});
	}
}
