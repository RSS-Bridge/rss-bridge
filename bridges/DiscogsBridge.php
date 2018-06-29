<?php

class DiscogsBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'DiscogsBridge';
	const URI = 'https://www.discogs.com/';
	const DESCRIPTION = 'Returns releases from discogs';
	const PARAMETERS = array(
		'Artist Releases' => array(
			'artistid' => array(
				'name' => 'Artist ID',
				'type' => 'number',
			)
		),
		'Label Releases' => array(
			'labelid' => array(
				'name' => 'Label ID',
				'type' => 'number',
			)
		),
		'User Wantlist' => array(
			'username_wantlist' => array(
				'name' => 'Username',
				'type' => 'text',
			)
		),
		'User Folder' => array(
			'username_folder' => array(
				'name' => 'Username',
				'type' => 'text',
			),
			'folderid' => array(
				'name' => 'Folder ID',
				'type' => 'number',
			)
		)
	);

	public function collectData() {

		if(!empty($this->getInput('artistid')) || !empty($this->getInput('labelid'))) {

			if(!empty($this->getInput('artistid'))) {
				$data = getContents('https://api.discogs.com/artists/'
						. $this->getInput('artistid')
						. '/releases?sort=year&sort_order=desc')
						or returnServerError('Unable to query discogs !');
			} elseif(!empty($this->getInput('labelid'))) {
				$data = getContents('https://api.discogs.com/labels/'
						. $this->getInput('labelid')
						. '/releases?sort=year&sort_order=desc')
						or returnServerError('Unable to query discogs !');
			}

			$jsonData = json_decode($data, true);
			foreach($jsonData['releases'] as $release) {

				$item = array();
				$item['author'] = $release['artist'];
				$item['title'] = $release['title'];
				$item['id'] = $release['id'];
				$resId = array_key_exists('main_release', $release) ? $release['main_release'] : $release['id'];
				$item['uri'] = self::URI . $this->getInput('artistid') . '/release/' . $resId;
				$item['timestamp'] = DateTime::createFromFormat('Y', $release['year'])->getTimestamp();
				$item['content'] = $item['author'] . ' - ' . $item['title'];
				$this->items[] = $item;
			}

		} elseif(!empty($this->getInput('username_wantlist')) || !empty($this->getInput('username_folder'))) {

			if(!empty($this->getInput('username_wantlist'))) {
				$data = getContents('https://api.discogs.com/users/'
						. $this->getInput('username_wantlist')
						. '/wants?sort=added&sort_order=desc')
						or returnServerError('Unable to query discogs !');
				$jsonData = json_decode($data, true)['wants'];

			} elseif(!empty($this->getInput('username_folder'))) {
				$data = getContents('https://api.discogs.com/users/'
						. $this->getInput('username_folder')
						. '/collection/folders/'
						. $this->getInput('folderid')
						.'/releases?sort=added&sort_order=desc')
						or returnServerError('Unable to query discogs !');
				$jsonData = json_decode($data, true)['releases'];
			}
			foreach($jsonData as $element) {

				$infos = $element['basic_information'];
				$item = array();
				$item['title'] = $infos['title'];
				$item['author'] = $infos['artists'][0]['name'];
				$item['id'] = $infos['artists'][0]['id'];
				$item['uri'] = self::URI . $infos['artists'][0]['id'] . '/release/' . $infos['id'];
				$item['timestamp'] = strtotime($element['date_added']);
				$item['content'] = $item['author'] . ' - ' . $item['title'];
				$this->items[] = $item;

			}
		}

	}

	public function getURI() {
		return self::URI;
	}

	public function getName() {
		return static::NAME;
	}
}
