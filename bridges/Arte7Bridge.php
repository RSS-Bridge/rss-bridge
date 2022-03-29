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
		'Catégorie (Français)' => array(
			'catfr' => array(
				'type' => 'list',
				'name' => 'Catégorie',
				'values' => array(
					'Toutes les vidéos (français)' => null,
					'Actu & société' => 'ACT',
					'Séries & fiction' => 'SER',
					'Cinéma' => 'CIN',
					'Arts & spectacles classiques' => 'ARS',
					'Culture pop' => 'CPO',
					'Découverte' => 'DEC',
					'Histoire' => 'HIST',
					'Science' => 'SCI',
					'Autre' => 'AUT'
				)
			)
		),
		'Collection (Français)' => array(
			'colfr' => array(
				'name' => 'Collection id',
				'required' => true,
				'title' => 'ex. RC-014095 pour https://www.arte.tv/fr/videos/RC-014095/blow-up/',
				'exampleValue'	=> 'RC-014095'
			)
		),
		'Catégorie (Allemand)' => array(
			'catde' => array(
				'type' => 'list',
				'name' => 'Catégorie',
				'values' => array(
					'Alle Videos (deutsch)' => null,
					'Aktuelles & Gesellschaft' => 'ACT',
					'Fernsehfilme & Serien' => 'SER',
					'Kino' => 'CIN',
					'Kunst & Kultur' => 'ARS',
					'Popkultur & Alternativ' => 'CPO',
					'Entdeckung' => 'DEC',
					'Geschichte' => 'HIST',
					'Wissenschaft' => 'SCI',
					'Sonstiges' => 'AUT'
				)
			)
		),
		'Collection (Allemand)' => array(
			'colde' => array(
				'name' => 'Collection id',
				'required' => true,
				'title' => 'ex. RC-014095 pour https://www.arte.tv/de/videos/RC-014095/blow-up/',
				'exampleValue'	=> 'RC-014095'
			)
		)
	);

	public function collectData(){
		switch($this->queriedContext) {
		case 'Catégorie (Français)':
			$category = $this->getInput('catfr');
			$lang = 'fr';
			break;
		case 'Collection (Français)':
			$lang = 'fr';
			$collectionId = $this->getInput('colfr');
			break;
		case 'Catégorie (Allemand)':
			$category = $this->getInput('catde');
			$lang = 'de';
			break;
		case 'Collection (Allemand)':
			$lang = 'de';
			$collectionId = $this->getInput('colde');
			break;
		}

		$url = 'https://api.arte.tv/api/opa/v3/videos?sort=-lastModified&limit=10&language='
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
