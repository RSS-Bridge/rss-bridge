<?php
class BakaUpdatesMangaReleasesBridge extends BridgeAbstract {
	const NAME = 'Baka Updates Manga Releases';
	const URI = 'https://www.mangaupdates.com/';
	const DESCRIPTION = 'Get the latest series releases';
	const MAINTAINER = 'fulmeek';
	const PARAMETERS = array(array(
		'series_id' => array(
			'name'		=> 'Series ID',
			'type'		=> 'number',
			'required'	=> true,
			'exampleValue'	=> '12345'
		)
	));
	const LIMIT_ITEMS = 10;

	private $feedName = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Series not found');

		$objTitle = $html->find('td[class="text pad"]', 1);
		if ($objTitle)
			$this->feedName = $objTitle->plaintext;

		$itemlist = $html->find('td#main_content table table table tr');
		if (!$itemlist)
			returnServerError('No releases');

		$limit = self::LIMIT_ITEMS;
		foreach($itemlist as $element) {
			$cols = $element->find('td[class="text pad"]');
			if (!$cols)
				continue;
			if ($limit <= 0)
				break;

			$item = array();
			$title = array();

			$item['content'] = '';

			$objDate = $element->find('td[class="text pad"]', 0);
			if ($objDate)
				$item['timestamp'] = strtotime($objDate->plaintext);

			$objTitle = $element->find('td[class="text pad"]', 1);
			if ($objTitle) {
				$title[] = html_entity_decode($objTitle->plaintext);
				$item['content'] .= '<p>Series: ' . $objTitle->innertext . '</p>';
			}

			$objVolume = $element->find('td[class="text pad"]', 2);
			if ($objVolume && !empty($objVolume->plaintext))
				$title[] = 'Vol.' . $objVolume->plaintext;

			$objChapter = $element->find('td[class="text pad"]', 3);
			if ($objChapter && !empty($objChapter->plaintext))
				$title[] = 'Chp.' . $objChapter->plaintext;

			$objAuthor = $element->find('td[class="text pad"]', 4);
			if ($objAuthor && !empty($objAuthor->plaintext)) {
				$item['author'] = html_entity_decode($objAuthor->plaintext);
				$item['content'] .= '<p>Groups: ' . $objAuthor->innertext . '</p>';
			}

			$item['title']	= implode(' ', $title);
			$item['uri']	= $this->getURI() . '#' . hash('sha1', $item['title']);

			$this->items[] = $item;

			if(count($this->items) >= $limit) {
				break;
			}
		}
	}

	public function getURI(){
		$series_id = $this->getInput('series_id');
		if (!empty($series_id)) {
			return self::URI . 'releases.html?search=' . $series_id . '&stype=series';
		}
		return self::URI;
	}

	public function getName(){
		if(!empty($this->feedName)) {
			return $this->feedName . ' - ' . self::NAME;
		}
		return parent::getName();
	}
}
