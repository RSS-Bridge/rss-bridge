<?php
class MozillaBugTrackerBridge extends BridgeAbstract {

	const NAME = 'Mozilla Bug Tracker';
	const URI = 'https://bugzilla.mozilla.org';
	const DESCRIPTION = 'Returns feeds for bug comments';
	const MAINTAINER = 'AntoineTurmel';
	const PARAMETERS = array(
		'Bug comments' => array(
			'id' => array(
				'name' => 'Bug tracking ID',
				'type' => 'number',
				'required' => true,
				'title' => 'Insert bug tracking ID',
				'exampleValue' => 121241
			),
			'limit' => array(
				'name' => 'Number of comments to return',
				'type' => 'number',
				'required' => false,
				'title' => 'Specify number of comments to return',
				'defaultValue' => -1
			),
			'sorting' => array(
				'name' => 'Sorting',
				'type' => 'list',
				'required' => false,
				'title' => 'Defines the sorting order of the comments returned',
				'defaultValue' => 'of',
				'values' => array(
					'Oldest first' => 'of',
					'Latest first' => 'lf'
				)
			)
		)
	);

	private $bugid = '';
	private $bugdesc = '';

	public function getIcon() {
		return self::URI . '/extensions/BMO/web/images/favicon.ico';
	}

	public function collectData(){
		$limit = $this->getInput('limit');
		$sorting = $this->getInput('sorting');

		// We use the print preview page for simplicity
		$html = getSimpleHTMLDOMCached($this->getURI() . '&format=multiple',
		86400,
		null,
		null,
		true,
		true,
		DEFAULT_TARGET_CHARSET,
		false, // Do NOT remove line breaks
		DEFAULT_BR_TEXT,
		DEFAULT_SPAN_TEXT);

		if($html === false)
			returnServerError('Failed to load page!');

		// Fix relative URLs
		defaultLinkTo($html, self::URI);

		// Store header information into private members
		$this->bugid = $html->find('#field-value-bug_id', 0)->plaintext;
		$this->bugdesc = $html->find('h1#field-value-short_desc', 0)->plaintext;

		// Get and limit comments
		$comments = $html->find('div.change-set');

		if($limit > 0 && count($comments) > $limit) {
			$comments = array_slice($comments, count($comments) - $limit, $limit);
		}

		if ($sorting === 'lf') {
			$comments = array_reverse($comments, true);
		}

		foreach($comments as $comment) {
			$comment = $this->inlineStyles($comment);

			$item = array();
			$item['uri'] = $comment->find('h3.change-name', 0)->find('a', 0)->href;
			$item['author'] = $comment->find('td.change-author', 0)->plaintext;
			$item['title'] = $comment->find('h3.change-name', 0)->plaintext;
			$item['timestamp'] = strtotime($comment->find('span.rel-time', 0)->title);
			$item['content'] = '';

			if ($comment->find('.comment-text', 0)) {
				$item['content'] = $comment->find('.comment-text', 0)->outertext;
			}

			if ($comment->find('div.activity', 0)) {
				$item['content'] .= $comment->find('div.activity', 0)->innertext;
			}

			$this->items[] = $item;
		}
	}

	public function getURI(){
		switch($this->queriedContext) {
			case 'Bug comments':
				return parent::getURI()
				. '/show_bug.cgi?id='
				. $this->getInput('id');
				break;
			default: return parent::getURI();
		}
	}

	public function getName(){
		switch($this->queriedContext) {
			case 'Bug comments':
				return $this->bugid
				. ' - '
				. $this->bugdesc
				. ' - '
				. parent::getName();
				break;
			default: return parent::getName();
		}
	}

	/**
	 * Adds styles as attributes to tags with known classes
	 *
	 * @param object $html A simplehtmldom object
	 * @return object Returns the original object with styles added as
	 * attributes.
	 */
	private function inlineStyles($html){
		foreach($html->find('.bz_closed') as $element) {
			$element->style = 'text-decoration:line-through;';
		}

		foreach($html->find('pre') as $element) {
			$element->style = 'white-space: pre-wrap;';
		}

		return $html;
	}
}
