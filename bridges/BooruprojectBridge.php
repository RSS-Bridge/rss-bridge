<?php
require_once('GelbooruBridge.php');

class BooruprojectBridge extends GelbooruBridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Booruproject';
	const URI = 'http://booru.org/';
	const DESCRIPTION = 'Returns images from given page of booruproject';
	const PARAMETERS = array(
		'global' => array(
			'p' => array(
				'name' => 'page',
				'type' => 'number'
			),
			't' => array(
				'name' => 'tags'
			)
		),
		'Booru subdomain (subdomain.booru.org)' => array(
			'i' => array(
				'name' => 'Subdomain',
				'required' => true
			)
		)
	);

	const PIDBYPAGE = 20;

	public function getURI(){
		if(!is_null($this->getInput('i'))) {
			return 'http://' . $this->getInput('i') . '.booru.org/';
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('i'))) {
			return static::NAME . ' ' . $this->getInput('i');
		}

		return parent::getName();
	}
}
