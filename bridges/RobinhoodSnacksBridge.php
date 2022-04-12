<?php

class RobinhoodSnacksBridge extends BridgeAbstract {
	const MAINTAINER = 'johnpc';
	const NAME = 'Robinhood Snacks Newsletter';
	const URI = 'https://snacks.robinhood.com/newsletters/';
	const CACHE_TIMEOUT = 86400; // 24h
	const DESCRIPTION = 'Returns newsletters from Robinhood Snacks';

	public function collectData()
	{
		$html = getSimpleHTMLDOM(self::URI);

		$elements = $html->find('#__next > div > div > div > div > div > a');

		foreach ($elements as $element) {
			if ($element->href === 'https://snacks.robinhood.com/newsletters/page/2/') {
				continue;
			}

			$this->items[] = array(
				'uri' => $element->href,
				'title' => $element->find('div > div', 3)->plaintext,
				'content' => $element->find('div > div', 4)->plaintext,
			);
		}
	}
}
