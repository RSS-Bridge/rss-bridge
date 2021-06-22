<?php
class Arte7Bridge extends BridgeAbstract {

	// const MAINTAINER = 'mitsukarenai';
	const NAME = 'Arte +7';
	const URI = 'https://www.arte.tv/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns newest videos from ARTE +7';

	const API_TOKEN = 'Nzc1Yjc1ZjJkYjk1NWFhN2I2MWEwMmRlMzAzNjI5NmU3NWU3ODg4ODJjOWMxNTMxYzEzZGRjYjg2ZGE4MmIwOA';

	const PARAMETERS = array(
		'global' => [
			'video_duration_filter' => [
				'name' => 'Exclude short videos',
				'type' => 'checkbox',
				'title' => 'Exclude videos that are shorter than 3 minutes',
				'defaultValue'	=> false,
			],
		],
		'Category' => array(
			'lang' => array(
				'type' => 'list',
				'name' => 'Language',
				'values' => array(
					'Français' => 'fr',
					'Deutsch' => 'de',
					'English' => 'en',
					'Español' => 'es',
					'Polski' => 'pl',
					'Italiano' => 'it'
				),
				'title' => 'ex. RC-014095 pour https://www.arte.tv/fr/videos/RC-014095/blow-up/',
				'exampleValue'	=> 'RC-014095'
			),
			'cat' => array(
				'type' => 'list',
				'name' => 'Category',
				'values' => array(
					'All videos' => null,
					'News & society' => 'ACT',
					'Series & fiction' => 'SER',
					'Cinema' => 'CIN',
					'Culture' => 'ARS',
					'Culture pop' => 'CPO',
					'Discovery' => 'DEC',
					'History' => 'HIST',
					'Science' => 'SCI',
					'Other' => 'AUT'
				)
			),
		),
		'Collection' => array(
			'lang' => array(
				'type' => 'list',
				'name' => 'Language',
				'values' => array(
					'Français' => 'fr',
					'Deutsch' => 'de',
					'English' => 'en',
					'Español' => 'es',
					'Polski' => 'pl',
					'Italiano' => 'it'
				)
			),
			'col' => array(
				'name' => 'Collection id',
				'required' => true,
				'title' => 'ex. RC-014095 pour https://www.arte.tv/de/videos/RC-014095/blow-up/',
				'exampleValue'	=> 'RC-014095'
			)
		)
	);

	public function collectData(){
		$lang = $this->getInput('lang');
		switch($this->queriedContext) {
		case 'Category':
			$category = $this->getInput('cat');
			$collectionId = null;
			break;
		case 'Collection':
			$collectionId = $this->getInput('col');
			$category = null;
			break;
		}

		$url = 'https://api.arte.tv/api/opa/v3/videos?sort=-lastModified&limit=15&language='
			. $lang
			. ($category != null ? '&category.code=' . $category : '')
			. ($collectionId != null ? '&collections.collectionId=' . $collectionId : '');

		$header = array(
			'Authorization: Bearer ' . self::API_TOKEN
		);

		$input = getContents($url, $header);
		$input_json = json_decode($input, true);

		foreach($input_json['videos'] as $element) {
			$durationSeconds = $element['durationSeconds'];

			if ($this->getInput('video_duration_filter') && $durationSeconds < 60 * 3) {
				continue;
			}

			$item = array();
			$item['uri'] = $element['url'];
			$item['id'] = $element['id'];

			$item['timestamp'] = strtotime($element['videoRightsBegin']);
			$item['title'] = $element['title'];

			if(!empty($element['subtitle']))
				$item['title'] = $element['title'] . ' | ' . $element['subtitle'];

			$durationMinutes = round((int)$durationSeconds / 60);
			$item['content'] = $element['teaserText']
			. '<br><br>'
			. $durationMinutes
			. 'min<br><a href="'
			. $item['uri']
			. '"><img src="'
			. $element['mainImage']['url']
			. '" /></a>';

			$this->items[] = $item;
		}
	}
}
