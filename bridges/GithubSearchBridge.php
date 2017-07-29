<?php
class GithubSearchBridge extends BridgeAbstract {

	const MAINTAINER = 'corenting';
	const NAME = 'Github Repositories Search';
	const URI = 'https://github.com/';
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = 'Returns a specified repositories search (sorted by recently updated)';
	const PARAMETERS = array( array(
		's' => array(
			'type' => 'text',
			'name' => 'Search query'
		)
	));

	public function collectData(){
		$params = array('utf8' => '✓',
										'q' => urlencode($this->getInput('s')),
										's' => 'updated',
										'o' => 'desc',
										'type' => 'Repositories');
		$url = self::URI . 'search?' . http_build_query($params);

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Error while downloading the website content');

		foreach($html->find('div.repo-list-item') as $element) {
			$item = array();

			$uri = $element->find('h3 a', 0)->href;
			$uri = substr(self::URI, 0, -1) . $uri;
			$item['uri'] = $uri;

			$title = $element->find('h3', 0)->plaintext;
			$item['title'] = $title;

			if (count($element->find('p')) == 2) {
				$content = $element->find('p', 0)->innertext;
			} else{
				$content = '';
			}
			$item['content'] = $content;

			$date = $element->find('relative-time', 0)->datetime;
			$item['timestamp'] = strtotime($date);

			$this->items[] = $item;
		}
	}
}
