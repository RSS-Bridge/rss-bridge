<?php
class VMwareSecurityBridge extends BridgeAbstract {

	const MAINTAINER = 'm0le.net';
	const NAME = 'VMware Security Advisories';
	const URI = 'https://www.vmware.com/security/advisories.html';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'VMware Security Advisories';
	const WEBROOT = 'https://www.vmware.com';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
		or returnServerError('Could not request VSA.');

		$html = defaultLinkTo($html, self::WEBROOT);

		$item = array();
		$articles = $html->find('div[class="news_block"]');

		foreach ($articles as $element) {
			$item['uri'] = $element->find('a', 0)->getAttribute('href');
			$title = $element->find('a', 0)->innertext;
			$item['title'] = $title;
			$item['timestamp'] = strtotime($element->find('p', 0)->innertext);
			$item['content'] = $element->find('p', 1)->innertext;
			$item['uid'] = $title;

			$this->items[] = $item;
		}
	}
}
