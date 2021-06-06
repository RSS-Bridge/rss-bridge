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
		'case_insensitive' => array(
			'name' => 'Case-insensitive filter',
			'type' => 'checkbox',
			'required' => false,
		),
		'fix_encoding' => array(
			'name' => 'Attempt Latin1/UTF-8 fixes when evaluating filter',
			'type' => 'checkbox',
			'required' => false,
		),
		'target_title' => array(
			'name' => 'Apply filter on title',
			'type' => 'checkbox',
			'required' => false,
			'defaultValue' => 'checked'
		),
		'target_content' => array(
			'name' => 'Apply filter on content',
			'type' => 'checkbox',
			'required' => false,
		),
		'target_author' => array(
			'name' => 'Apply filter on author',
			'type' => 'checkbox',
			'required' => false,
		),
		'title_from_content' => array(
			'name' => 'Generate title from content (overwrite existing title)',
			'type' => 'checkbox',
			'required' => false,
		),
		'length_limit' => array(
			'name' => 'Max length analyzed by filter (-1: no limit)',
			'type' => 'number',
			'required' => false,
			'defaultValue' => -1,
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
		if($this->getInput('target_title')) {
			$filter_fields[] = $item['title'];
		}
		if($this->getInput('target_content')) {
			$filter_fields[] = $item['content'];
		}
		if($this->getInput('target_author')) {
			$filter_fields[] = $item['author'];
		}

		// Apply filter on item
		$keep_item = false;
		$length_limit = intval($this->getInput('length_limit'));
		foreach($filter_fields as $field) {
			if($length_limit > 0) {
				$field = substr($field, 0, $length_limit);
			}
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
