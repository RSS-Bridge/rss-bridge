<?php

class FilterBridge extends FeedExpander {

	const MAINTAINER = 'Frenzie, ORelio';
	const NAME = 'Filter';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Filters a feed of your choice';
	const URI = 'https://github.com/RSS-Bridge/rss-bridge';

	const PARAMETERS = array(array(
		'url' => array(
			'name' => 'Feed URL',
			'required' => true,
		),
		'filter' => array(
			'name' => 'Filter (regular expression)',
			'required' => false,
		),
		'case_insensitive' => array(
			'name' => 'Case-insensitive regular expression',
			'type' => 'checkbox',
			'required' => false,
		),
		'filter_type' => array(
			'name' => 'Filter type',
			'type' => 'list',
			'required' => false,
			'values' => array(
				'Keep matching items' => 'permit',
				'Hide matching items' => 'block',
			),
			'defaultValue' => 'permit',
		),
		'filter_target' => array(
			'name' => 'Apply regex on field',
			'type' => 'list',
			'required' => false,
			'values' => array(
				'Title' => 'title',
				'Content' => 'content',
				'Title and Content' => 'title_content',
			),
			'defaultValue' => 'title',
		),
		'filter_content_limit' => array(
			'name' => 'Max content length analyzed by filter (-1: no limit)',
			'type' => 'number',
			'required' => false,
			'defaultValue' => -1,
		),
		'title_from_content' => array(
			'name' => 'Generate title from content (overwrite existing title)',
			'type' => 'checkbox',
			'required' => false,
		),
		'fix_encoding' => array(
			'name' => 'Attempt Latin1/UTF-8 fixes when evaluating regex',
			'type' => 'checkbox',
			'required' => false,
		),
	));

	protected function parseItem($newItem){
		$item = parent::parseItem($newItem);

		// Generate title from first 50 characters of content?
		if($this->getInput('title_from_content') && array_key_exists('content', $item)) {
			$content = str_get_html($item['content']);
			$pos = strpos($item['content'], ' ', 50);
			$item['title'] = substr($content->plaintext, 0, $pos);
			if(strlen($content->plaintext) >= $pos) {
				$item['title'] .= '...';
			}
		}

		// Build regular expression
		$regex = '/' . $this->getInput('filter') . '/';
		if($this->getInput('case_insensitive')) {
			$regex .= 'i';
		}

		// Retrieve fields to check
		$filter_fields = array();
		$filter_target = $this->getInput('filter_target');
		if(strpos($filter_target, 'title') !== false) {
			$filter_fields []= $item['title'];
		}
		if(strpos($filter_target, 'content') !== false) {
			$filter_content_limit = intval($this->getInput('filter_content_limit'));
			if($filter_content_limit > 0) {
				$filter_fields []= substr($item['content'], 0, $filter_content_limit);
			} else {
				$filter_fields []= $item['content'];
			}
		}

		// Apply filter on item
		$keep_item = false;
		foreach($filter_fields as $field) {
			$keep_item |= boolval(preg_match($regex, $field));
			if($this->getInput('fix_encoding')) {
				$keep_item |= boolval(preg_match($regex, utf8_decode($field)));
				$keep_item |= boolval(preg_match($regex, utf8_encode($field)));
			}
		}

		// Reverse result? (keep everything but matching items)
		if($this->getInput('filter_type') === 'block') {
			$keep_item = !$keep_item;
		}

		return $keep_item ? $item : null;
	}

	public function getURI(){
		$url = $this->getInput('url');

		if(empty($url)) {
			$url = parent::getURI();
		}

		return $url;
	}

	public function collectData(){
		if($this->getInput('url') && substr($this->getInput('url'), 0, 4) !== 'http') {
			// just in case someone finds a way to access local files by playing with the url
			returnClientError('The url parameter must either refer to http or https protocol.');
		}
		$this->collectExpandableDatas($this->getURI());
	}
}
