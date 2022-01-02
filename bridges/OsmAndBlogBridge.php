<?php
class OsmAndBlogBridge extends BridgeAbstract {
	const NAME = 'OsmAnd Blog';
	const URI = 'https://osmand.net/';
	const DESCRIPTION = 'Get the latest news from OsmAnd.net';
	const MAINTAINER = 'fulmeek';

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI . 'blog');

		foreach($html->find('div.article') as $element) {
			$item = array();

			$objTitle = $element->find('h1', 0);
			if (!$objTitle)
				$objTitle = $element->find('h2', 0);
			if (!$objTitle)
				$objTitle = $element->find('h3', 0);
			if ($objTitle)
				$item['title'] = $objTitle->plaintext;

			$objDate = $element->find('meta[pubdate]', 0);
			if ($objDate) {
				$item['timestamp'] = strtotime($objDate->pubdate);
			} else {
				$objDate = $element->find('.date', 0);
				if ($objDate)
					$item['timestamp'] = strtotime($objDate->plaintext);
			}

			$this->cleanupContent($element, $objTitle, $objDate, $element->find('.date', 0));
			$item['content'] = $element->innertext;

			$objLink = $html->find('.articlelinklist a', 0);
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
