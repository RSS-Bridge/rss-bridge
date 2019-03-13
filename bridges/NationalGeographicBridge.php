<?php
class NationalGeographicBridge extends BridgeAbstract {

	const NAME = 'National Geographic';
	const URI = 'https://www.nationalgeographic.com/';
	const DESCRIPTION = 'Fetches the latest news from National Geographic';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 3600;

	const PARAMETERS = array(

	);

	public function collectData() {

		$html = getSimpleHTMLDOM(static::URI)
			or returnServerError('Could not request ' . static::URI);

		$script = $html->find('#lead-component script')[0];

		$json = json_decode($script->innertext, true);

		foreach($json['body']['0']['homepage_package']['cards'] as $card) {

			$item = array();

			// Find title component
			// TODO: Make a function of this => getComponent($card, $content_type) : object
			foreach($card['components'] as $component)
				if ($component['content_type'] === 'title')
					break;

			if ($component['content_type'] !== 'title')
				continue;

			$title = $component;

			// Find dek component
			foreach($card['components'] as $component)
				if ($component['content_type'] === 'dek')
					break;

			if ($component['content_type'] === 'dek')
				$item['content'] = $component['dek']['text'];

			$item['uid'] = $card['id'];
			$item['uri'] = $card['uri'];
			$item['title'] = $title['title']['text'];
			// $item['timestamp'] = ;
			// $item['author'] = ;
			$item['enclosures'] = array($card['promo_image']['image']['uri']);
			// $item['categories'] = array();

			$this->items[] = $item;
		}
	}
}
