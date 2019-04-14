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
	);

	public function getIcon() {
		$html = getSimpleHTMLDOMCached(self::URI)
			or returnServerError('Could not load content');
		
		return $html->find('head link[rel="apple-touch-icon"]', 0)->getAttribute('href');
	}

	public function getURI() {
		$uri = self::URI;
		switch ($this->queriedContext) {
			case 'Program':
				$uri .= '/programs/' . $this->getInput('program');
		}
		return $uri;
	}

	public function getName() {
		switch ($this->queriedContext) {
			case 'Program':
				$html = getSimpleHTMLDOMCached($this->getURI())
					or returnServerError('Could not load content');

				return $html->find('h1.program--title', 0)->innertext;
				break;
		}

		return parent::getName();
	}

	/* This uses the uscreen platform, other sites can adapt this. https://www.uscreen.tv/ */
	public function collectData() {
		switch ($this->queriedContext) {
		case 'Program':
			$program = $this->getInput('program');
			$html = getSimpleHTMLDOMCached($this->getURI())
				or returnServerError('Could not load content');

			foreach($html->find('.cce--thumbnails-video-chapter') as $element) {
				$cid = $element->getAttribute('data-id');
				$item['title'] = $element->find('.cce--chapter-title', 0)->innertext;
				$item['content'] = $element->find('.cce--thumbnails-image-block', 0) . $element->find('.cce--chapter-body', 0)->innertext;
				$item['uri'] = $this->getURI() . '?cid=' . $cid;

				/* TODO: Suport login credentials? */
				/* # Get direct video URL */
				/* $json_source = getContents(self::URI . '/chapters/' . $cid, array('Cookie: _uscreen2_session=???;')) */
				/* 	or returnServerError('Could not request chapter JSON'); */
				/* $json = json_decode($json_source); */

				/* $item['enclosures'] = [$json->fallback]; */

				$this->items[] = $item;
			}
		}
	}
}
