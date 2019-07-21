<?php
class FabriceBellardBridge extends BridgeAbstract {
	const NAME = 'Fabrice Bellard';
	const URI = 'https://bellard.org/';
	const DESCRIPTION = "Fabrice Bellard's Home Page";
	const MAINTAINER = 'somini';

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not load content');

		foreach ($html->find('p') as $obj) {
			$item = array();

			$links = $obj->find('a');

			$link_uri = self::URI;
			if (count($links) > 0) {
				/* Fix relative links */
				foreach ($links as $link) {
					if (strpos($link, '://') === false) {
						$link->href = self::URI . $link->href;
					}
				}
				$link_uri = $links[0]->href;
				if ($link_uri[-1] !== '/') {
					$link_uri = $link_uri . '/';
				}
			}

			$item['title'] = strip_tags($obj->innertext);
			$item['uri'] = $link_uri;
			$item['content'] = $obj->innertext;

			$this->items[] = $item;
		}
	}
}
