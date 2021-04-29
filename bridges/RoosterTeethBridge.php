<?php

class RoosterTeethBridge extends BridgeAbstract {

	const MAINTAINER = 'tgkenney';
	const NAME = 'Rooster Teeth';
	const URI = 'https://roosterteeth.com';
	const DESCRIPTION = 'Gets the latest channel videos from the Rooster Teeth website';
	const API = 'https://svod-be.roosterteeth.com/';

	const PARAMETERS = array(
		'Options' => array(
			'channel' => array(
				'type' => 'list',
				'name' => 'Channel',
				'title' => 'Select a channel to filter by',
				'values' => array(
					'All channels' => 'all',
					'Achievement Hunter' => 'achievement-hunter',
					'Cow Chop' => 'cow-chop',
					'Death Battle' => 'death-battle',
					'Funhaus' => 'funhaus',
					'Inside Gaming' => 'inside-gaming',
					'JT Music' => 'jt-music',
					'Kinda Funny' => 'kinda-funny',
					'Rooster Teeth' => 'rooster-teeth',
					'Sugar Pine 7' => 'sugar-pine-7'
				)
			),
			'sort' => array(
				'type' => 'list',
				'name' => 'Sort',
				'title' => 'Select a sort order',
				'values' => array(
					'Newest -> Oldest' => 'desc',
					'Oldest -> Newest' => 'asc'
				),
				'defaultValue' => 'desc'
			),
			'first' => array(
				'type' => 'list',
				'name' => 'RoosterTeeth First',
				'title' => 'Select whether to include "First" videos before they are public',
				'values' => array(
					'True' => true,
					'False' => false
				)
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'required' => false,
				'title' => 'Maximum number of items to return',
				'defaultValue' => 10
			)
		)
	);

	public function collectData() {
		if ($this->getInput('channel') !== 'all') {
			$uri = self::API
				. 'api/v1/episodes?per_page='
				. $this->getInput('limit')
				. '&channel_id='
				. $this->getInput('channel')
				. '&order=' . $this->getInput('sort')
				. '&page=1';

			$htmlJSON = getSimpleHTMLDOM($uri)
			or returnServerError('Could not contact Rooster Teeth: ' . $uri);
		} else {
			$uri = self::API
				. '/api/v1/episodes?per_page='
				. $this->getInput('limit')
				. '&filter=all&order='
				. $this->getInput('sort')
				. '&page=1';

			$htmlJSON = getSimpleHTMLDOM($uri)
			or returnServerError('Could not contact Rooster Teeth: ' . $uri);
		}

		$htmlArray = json_decode($htmlJSON, true);

		foreach($htmlArray['data'] as $key => $value) {
			$item = array();

			if (!$this->getInput('first') && $value['attributes']['is_sponsors_only']) {
				continue;
			}

			$publicDate = date_create($value['attributes']['member_golive_at']);
			$dateDiff = date_diff($publicDate, date_create(), false);

			if (!$this->getInput('first') && $dateDiff->invert == 1) {
				continue;
			}

			$item['uri'] = self::URI . $value['canonical_links']['self'];
			$item['title'] = $value['attributes']['title'];
			$item['timestamp'] = $value['attributes']['member_golive_at'];
			$item['author'] = $value['attributes']['show_title'];

			$this->items[] = $item;
		}
	}
}
