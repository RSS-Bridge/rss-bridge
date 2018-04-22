<?php

class FilterBridge extends FeedExpander {

	const MAINTAINER = 'Frenzie';
	const NAME = 'Filter';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Filters a feed of your choice';

	const PARAMETERS = array(array(
		'url' => array(
			'name' => 'Feed URL',
			'required' => true,
		),
		'filter' => array(
			'name' => 'Filter item title (regular expression)',
			'required' => false,
		),
		'filter_type' => array(
			'name' => 'Filter type',
			'type' => 'list',
			'required' => false,
			'values' => array(
				'Permit' => 'permit',
				'Block' => 'block',
			),
			'defaultValue' => 'permit',
		),
	));

	protected function parseItem($newItem){
		$item = parent::parseItem($newItem);

		switch(true) {
		case $this->getFilterType() === 'permit':
			if (preg_match($this->getFilter(), $item['title'])) {
				return $item;
			}
			break;
		case $this->getFilterType() === 'block':
			if (!preg_match($this->getFilter(), $item['title'])) {
				return $item;
			}
			break;
		}
		return null;
	}

	protected function getFilter(){
		return '/' . $this->getInput('filter') . '/';
	}

	protected function getFilterType(){
		return $this->getInput('filter_type');
	}

	public function getURI(){
		$url = $this->getInput('url');

		if(empty($url)) {
			$url = parent::getURI();
		}
		return $url;
	}

	public function collectData(){
		if($this->getInput('url') && substr($this->getInput('url'), 0, strlen('http')) !== 'http') {
			// just in case someone find a way to access local files by playing with the url
			returnClientError('The url parameter must either refer to http or https protocol.');
		}
		try{
			$this->collectExpandableDatas($this->getURI());
		} catch (HttpException $e) {
			$this->collectExpandableDatas($this->getURI());
		}
	}
}
