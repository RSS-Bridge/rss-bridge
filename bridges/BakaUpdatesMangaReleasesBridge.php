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
	const LIMIT_COLS = 5;
	const LIMIT_ITEMS = 10;

	private $feedName = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Series not found');

		// content is an unstructured pile of divs, ugly to parse
		$cols = $html->find('div#main_content div.row > div.text');
		if (!$cols)
			returnServerError('No releases');

		$rows = array_slice(
			array_chunk($cols, self::LIMIT_COLS), 0, self::LIMIT_ITEMS
		);

		if (isset($rows[0][1])) {
			$this->feedName = $this->filterHTML($rows[0][1]->plaintext);
		}

		foreach($rows as $cols) {
			if (count($cols) < self::LIMIT_COLS) continue;

			$item = array();
			$title = array();

			$item['content'] = '';

			$objDate = $cols[0];
			if ($objDate)
				$item['timestamp'] = strtotime($objDate->plaintext);

			$objTitle = $cols[1];
			if ($objTitle) {
				$title[] = $this->filterHTML($objTitle->plaintext);
				$item['content'] .= '<p>Series: ' . $this->filterText($objTitle->innertext) . '</p>';
			}

			$objVolume = $cols[2];
			if ($objVolume && !empty($objVolume->plaintext))
				$title[] = 'Vol.' . $objVolume->plaintext;

			$objChapter = $cols[3];
			if ($objChapter && !empty($objChapter->plaintext))
				$title[] = 'Chp.' . $objChapter->plaintext;

			$objAuthor = $cols[4];
			if ($objAuthor && !empty($objAuthor->plaintext)) {
				$item['author'] = $this->filterHTML($objAuthor->plaintext);
				$item['content'] .= '<p>Groups: ' . $this->filterText($objAuthor->innertext) . '</p>';
			}

			$item['title'] = implode(' ', $title);
			$item['uri'] = $this->getURI();
			$item['uid'] = $this->getSanitizedHash($item['title']);

			$this->items[] = $item;
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

	private function getSanitizedHash($string) {
		return hash('sha1', preg_replace('/[^a-zA-Z0-9\-\.]/', '', ucwords(strtolower($string))));
	}

	private function filterText($text) {
		return rtrim($text, '* ');
	}

	private function filterHTML($text) {
		return $this->filterText(html_entity_decode($text));
	}
}
