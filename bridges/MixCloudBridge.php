<?php

class MixCloudBridge extends BridgeAbstract {

	const MAINTAINER = 'Alexis CHEMEL';
	const NAME = 'MixCloud';
	const URI = 'https://www.mixcloud.com';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns latest musics on user stream';

	const PARAMETERS = array(array(
		'u' => array(
			'name' => 'username',
			'required' => true,
			'exampleValue' => 'DJJazzyJeff',
		)
	));

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return 'MixCloud - ' . $this->getInput('u');
		}

		return parent::getName();
	}

	public function collectData(){
		$mixcloudUri = 'https://api.mixcloud.com/' . $this->getInput('u') . '/feed/';
		$content = getContents($mixcloudUri);
		$casts = json_decode($content)->data;
		$castTypes = array('upload','listen');

		foreach($casts as $cast) {
			if (! in_array($cast->type, $castTypes)) {
				// Skip unwanted feed entries (follow, comment, favorite, etc)
				continue;
			}

			$item = array();

			$item['uri'] = $cast->cloudcasts[0]->url;
			$item['title'] = $cast->cloudcasts[0]->name;
			$item['content'] = '<img src="' . $cast->cloudcasts[0]->pictures->thumbnail . '" />';
			$item['author'] = $cast->cloudcasts[0]->user->name;
			$item['timestamp'] = $cast->created_time;

			$this->items[] = $item;
		}
	}
}
