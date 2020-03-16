<?php
class GithubTrendingBridge extends BridgeAbstract {

	const MAINTAINER = 'liamka';
	const NAME = 'Github Trending';
	const URI = 'https://github.com/trending';
	const CACHE_TIMEOUT = 43200; // 12hr
	const DESCRIPTION = 'See what the GitHub community is most excited repos.';
	const PARAMETERS = array(
		'Language' => array(
			'language' => array(
				'name' => 'Programming language',
				'required' => true
			)
		),
		'global' => array(
			'date_range' => array(
				'name' => 'Date range',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'Today' => 'today',
					'Weekly' => 'weekly',
					'Monthly' => 'monthly',
				),
				'defaultValue' => 'today'
			)
		)

	);

	public function collectData(){
		$params = array('since' => urlencode($this->getInput('date_range')));
		$url = self::URI . '/' . $this->getInput('language') . '?' . http_build_query($params);

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Error while downloading the website content');

		foreach($html->find('.Box-row') as $element) {
			$item = array();

			// URI
			$item['uri'] = substr(self::URI, 0, -1) . $element->find('h1 a', 0)->href;

			// Title
			$item['title'] = str_replace('  ', '', trim(strip_tags($element->find('h1 a', 0)->plaintext)));

			// Description
			$item['description'] = trim(strip_tags($element->find('p.text-gray', 0)->innertext));

			// Time
			$item['timestamp'] = time();

			// TODO: Proxy?
			$this->items[] = $item;
		}
	}

	public function getName(){
		if(!is_null($this->getInput('language'))) {
			return self::NAME . ' - ' . $this->getInput('language');
		}

		return parent::getName();
	}
}
