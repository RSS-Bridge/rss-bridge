<?php
class ComicsKingdomBridge extends BridgeAbstract {

	const MAINTAINER = 'stjohnjohnson';
	const NAME = 'Comics Kingdom Unofficial RSS';
	const URI = 'https://www.comicskingdom.com/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Comics Kingdom Unofficial RSS';
	const PARAMETERS = array( array(
		'comicname' => array(
			'name' => 'comicname',
			'type' => 'text',
			'required' => true
		)
	));

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI(), array(), array(), true, false)
			or returnServerError('Could not request Comics Kingdom: ' . $this->getURI());

		// Get author from first page
		$author = $html->find('div.author p', 0)->plaintext
			or returnServerError('Comics Kingdom comic does not exist: ' . $this->getURI());;

		// Get current date/link
		$link = $html->find('meta[property=og:url]', 0)->content;
		for($i = 0; $i < 5; $i++) {
			$item = array();

			$page = getSimpleHTMLDOM($link)
				or returnServerError('Could not request Comics Kingdom: ' . $link);

			$imagelink = $page->find('meta[property=og:image]', 0)->content;
			$prevSlug = $page->find('slider-arrow[:is-left-arrow=true]', 0);
			$link = $this->getURI() . '/' . $prevSlug->getAttribute('date-slug');

			$date = explode('/', $link);

			$item['id'] = $imagelink;
			$item['uri'] = $link;
			$item['author'] = $author;
			$item['title'] = 'Comics Kingdom ' . $this->getInput('comicname');
			$item['timestamp'] = DateTime::createFromFormat('Y-m-d', $date[count($date) - 1])->getTimestamp();
			$item['content'] = '<img src="' . $imagelink . '" />';

			$this->items[] = $item;
		}
	}

	public function getURI(){
		if(!is_null($this->getInput('comicname'))) {
			return self::URI . urlencode($this->getInput('comicname'));
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('comicname'))) {
			return $this->getInput('comicname') . ' - Comics Kingdom';
		}

		return parent::getName();
	}
}
