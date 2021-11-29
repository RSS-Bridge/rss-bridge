<?php
class WulffmorgenthalerBridge extends BridgeAbstract {
	const NAME = 'Wulffmorgenthaler strip';
	const URI = 'http://wulffmorgenthaler.com/';
	const DESCRIPTION = 'Wulffmorgenthaler comic';
	const MAINTAINER = 'fatuus';
	const CACHE_TIMEOUT = 28800; #8h

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not load content');

		foreach($html->find('article') as $element) {
			$item = array();

			$objTitle = $element->find('time', 0);
			if ($objTitle)
				$item['title'] = $objTitle->plaintext;

			$objDate = $element->find('div.box-content a', 0)->alt;
			if ($objDate) {
				$item['timestamp'] = strtotime($objDate->pubdate);
			} else {
				$objDate = $element->find('time', 0);
				if ($objDate)
					$item['timestamp'] = strtotime($objDate->plaintext);
			}

			$this->cleanupContent($element, $objTitle, $objDate, $element->find('.date', 0));

			$objContent = $element->find('div.box-content img', 0);
			if ($objTitle) {
				$item['content'] = $objContent;
			} else {
				$item['content'] = $element->innertext;
			}

			$objLink = $element->find('div.box-content a', 0);
			if ($objLink) {
				$item['uri'] = $this->filterURL($objLink->href);
			} else {
				$item['uri'] = 'urn:sha1:' . hash('sha1', $item['content']);
			}

			$this->items[] = $item;
		}
	}

	private function filterURL($url) {
		if (strpos($url, '://') === false)
			return self::URI . ltrim($url, '/');
		return $url;
	}

	private function cleanupContent($content, ...$removeItems) {
		foreach ($removeItems as $obj) {
			if ($obj) $obj->outertext = '';
		}
		foreach ($content->find('img') as $obj) {
			$obj->src = $this->filterURL($obj->src);
		}
		foreach ($content->find('a') as $obj) {
			$obj->href = $this->filterURL($obj->href);
			$obj->target = '_blank';
		}
	}
}
