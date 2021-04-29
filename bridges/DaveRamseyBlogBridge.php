<?php

class DaveRamseyBlogBridge extends BridgeAbstract {
	const MAINTAINER = 'johnpc';
	const NAME = 'Dave Ramsey Blog';
	const URI = 'https://www.daveramsey.com/blog';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Returns blog posts from daveramsey.com';

	public function collectData()
	{
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request daveramsey.com.');

		foreach ($html->find('.Post') as $element) {
			$this->items[] = array(
				'uri' => 'https://www.daveramsey.com' . $element->find('header > a', 0)->href,
				'title' => $element->find('header > h2 > a', 0)->plaintext,
				'tags' => $element->find('.Post-topic', 0)->plaintext,
				'content' => $element->find('.Post-body', 0)->plaintext,
			);
		}
	}
}
