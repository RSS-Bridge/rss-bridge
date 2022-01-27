<?php
class QPlayBridge extends BridgeAbstract {
	const NAME = 'Q Play';
	const URI = 'https://www.qplay.pt';
	const DESCRIPTION = 'Entretenimento e humor em Português';
	const MAINTAINER = 'somini';
	const PARAMETERS = array(
		'Program' => array(
			'program' => array(
				'name' => 'Program Name',
				'type' => 'text',
				'required' => true,
			),
		),
		'Catalog' => array(
			'all_pages' => array(
				'name' => 'All Pages',
				'type' => 'checkbox',
				'defaultValue' => false,
			),
		),
	);

	public function getIcon() {
		# This should be the favicon served on `self::URI`
		return 'https://s3.amazonaws.com/unode1/assets/4957/r3T9Lm9LTLmpAEX6FlSA_apple-touch-icon.png';
	}

	public function getURI() {
		switch ($this->queriedContext) {
			case 'Program':
				return self::URI . '/programs/' . $this->getInput('program');
			case 'Catalog':
				return self::URI . '/catalog';
		}
		return parent::getURI();
	}

	public function getName() {
		switch ($this->queriedContext) {
			case 'Program':
				$html = getSimpleHTMLDOMCached($this->getURI());

				return $html->find('h1.program--title', 0)->innertext;
			case 'Catalog':
				return self::NAME . ' | Programas';
		}

		return parent::getName();
	}

	/* This uses the uscreen platform, other sites can adapt this. https://www.uscreen.tv/ */
	public function collectData() {
		switch ($this->queriedContext) {
		case 'Program':
			$program = $this->getInput('program');
			$html = getSimpleHTMLDOMCached($this->getURI());

			foreach($html->find('.cce--thumbnails-video-chapter') as $element) {
				$cid = $element->getAttribute('data-id');
				$item['title'] = $element->find('.cce--chapter-title', 0)->innertext;
				$item['content'] = $element->find('.cce--thumbnails-image-block', 0)
					. $element->find('.cce--chapter-body', 0)->innertext;
				$item['uri'] = $this->getURI() . '?cid=' . $cid;

				/* TODO: Suport login credentials? */
				/* # Get direct video URL */
				/* $json_source = getContents(self::URI . '/chapters/' . $cid, array('Cookie: _uscreen2_session=???;')); */
				/* $json = json_decode($json_source); */

				/* $item['enclosures'] = [$json->fallback]; */

				$this->items[] = $item;
			}

			break;
		case 'Catalog':
			$json_raw = getContents($this->getCatalogURI(1));

			$json = json_decode($json_raw);
			$total_pages = $json->total_pages;

			foreach($this->parseCatalogPage($json) as $item) {
				$this->items[] = $item;
			}

			if ($this->getInput('all_pages') === true) {
				foreach(range(2, $total_pages) as $page) {
					$json_raw = getContents($this->getCatalogURI($page));

					$json = json_decode($json_raw);

					foreach($this->parseCatalogPage($json) as $item) {
						$this->items[] = $item;
					}
				}
			}

			break;
		}
	}

	private function getCatalogURI($page) {
		return self::URI . '/catalog.json?page=' . $page;
	}

	private function parseCatalogPage($json) {
		$items = array();

		foreach($json->records as $record) {
			$item = array();

			$item['title'] = $record->title;
			$item['content'] = $record->description
				. '<div>Duration: ' . $record->duration . '</div>';
			$item['timestamp'] = strtotime($record->release_date);
			$item['uri'] = self::URI . $record->url;
			$item['enclosures'] = array(
				$record->main_poster,
			);

			$items[] = $item;
		}

		return $items;
	}
}
