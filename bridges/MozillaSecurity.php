<?php
class MozillaSecurityBridge extends BridgeAbstract {

			const MAINTAINER = 'm0le.net';
			const NAME = 'Mozilla Security Advisories';
			const URI = 'https://www.mozilla.org/en-US/security/advisories/';
			const CACHE_TIMEOUT = 7200; // 2h
			const DESCRIPTION = 'Mozilla Security Advisories';
			const WEBROOT = 'https://www.mozilla.org/';

			public function collectData(){
					$html = getSimpleHTMLDOM(self::URI)
							or returnServerError('Could not request MSA.');

					$item = array();
					$articles = $html->find('div[itemprop="articleBody"] h2');

					foreach ($articles as $element) {
							$item['title'] = $element->innertext;
							$item['timestamp'] = strtotime($element->innertext);
							$item['content'] = str_replace('href="/', 'href="' . self::WEBROOT, $element->next_sibling()->innertext);
							$item['uri'] = self::URI;
					$this->items[] = $item;
					}
			}
}
