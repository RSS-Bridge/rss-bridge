<?php
class GoComicsBridge extends BridgeAbstract {

	const MAINTAINER = 'sky';
	const NAME = 'GoComics Unofficial RSS';
	const URI = 'http://www.gocomics.com/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'The Unofficial GoComics RSS';
	const PARAMETERS = array( array(
		'comicname' => array(
			'name' => 'comicname',
			'type' => 'text',
			'required' => true
		)
	));

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request GoComics: ' . $this->getURI());

		foreach($html->find('div.comic__container') as $element) {

			$img = $element->find('.item-comic-image img', 0);
			$link = $element->find('a.js-item-comic-link', 0);
			$comic = $img->src;
			$title = $link->title;
			$url = $html->find('input.js-copy-link', 0)->value;
			$date = substr($title, -10);
			if (empty($title))
				$title = 'GoComics ' . $this->getInput('comicname') . ' on ' . $date;
			$date = strtotime($date);

			$item = array();
			$item['id'] = $url;
			$item['uri'] = $url;
			$item['title'] = $title;
			$item['author'] = preg_replace('/by /', '', $element->find('a.link-blended small', 0)->plaintext);
			$item['timestamp'] = $date;
			$item['content'] = '<img src="' . $comic . '" alt="' . $title . '" />';
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
			return $this->getInput('comicname') . ' - GoComics';
		}

		return parent::getName();
	}
}
