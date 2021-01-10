<?php

class ItchioBridge extends BridgeAbstract {
	const NAME = 'itch.io';
	const URI = 'https://itch.io';
	const DESCRIPTION = 'Fetches the file uploads for a product';
	const MAINTAINER = 'jacquesh';
	const PARAMETERS = array(array(
		'url' => array(
			'name' => 'Product URL',
			'exampleValue' => 'https://remedybg.itch.io/remedybg',
			'required' => true,
		)
	));
	const CACHE_TIMEOUT = 21600; // 6 hours

	public function collectData() {
		$url = $this->getInput('url');
		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request: ' . $url);

		$title = $html->find('.game_title', 0)->innertext;
		$timestampOriginal = $html->find('span.icon-stopwatch', 0)->parent()->title;
		$timestampFormatted = str_replace('@', '', $timestampOriginal);

		$content = 'The following files are available to download:<br/>';
		foreach ($html->find('div.upload') as $element) {
			$filename = $element->find('strong.name', 0)->innertext;
			$filesize = $element->find('span.file_size', 0)->first_child()->innertext;
			$content = $content . $filename . ' (' . $filesize . ')<br/>';
		}

		// NOTE: At the time of writing it is not clear under which conditions
		// itch updates the timestamp. In case they don't always update it,
		// we include the file list as well when computing the UID hash.
		$uidContent = $timestampFormatted . $content;

		$item = array();
		$item['uri'] = $url;
		$item['uid'] = $uidContent;
		$item['title'] = 'New release for ' . $title;
		$item['content'] = $content;
		$item['timestamp'] = $timestampFormatted;
		$this->items[] = $item;
	}
}
