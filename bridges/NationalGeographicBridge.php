<?php
class NationalGeographicBridge extends BridgeAbstract {

	const NAME = 'National Geographic';
	const URI = 'https://www.nationalgeographic.com/magazine/';
	const DESCRIPTION = 'Fetches the latest articles from the National Geographic Magazine';
	const MAINTAINER = 'logmanoriginal';

	public function collectData() {

		$html = getSimpleHTMLDOM(static::URI)
			or returnServerError('Could not request ' . static::URI);

		$script = $html->find('#lead-component script')[0];

		$json = json_decode($script->innertext, true);

		foreach($json['body']['10']['card_stack']['cards'] as $card) {

			$item = array();

			$item['uri'] = $card['uri'];
			$item['title'] = $card['title'];
			$item['enclosures'] = array($card['image']['uri']);

			$this->items[] = $item;

		}
	}
}
