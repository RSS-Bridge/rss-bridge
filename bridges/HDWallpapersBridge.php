<?php
class HDWallpapersBridge extends BridgeAbstract {
	const MAINTAINER = 'nel50n';
	const NAME = 'HD Wallpapers Bridge';
	const URI = 'https://www.hdwallpapers.in/';
	const CACHE_TIMEOUT = 43200; //12h
	const DESCRIPTION = 'Returns the latests wallpapers from HDWallpapers';

	const PARAMETERS = array( array(
		'c' => array(
			'name' => 'category',
			'defaultValue' => 'latest_wallpapers'
		),
		'm' => array(
			'name' => 'max number of wallpapers'
		),
		'r' => array(
			'name' => 'resolution',
			'defaultValue' => 'HD',
			'exampleValue' => 'HD, 1920x1200, 1680x1050,…'
		)
	));

	public function collectData(){
		$category = $this->getInput('c');
		if(strrpos($category, 'wallpapers') !== strlen($category) - strlen('wallpapers')) {
			$category .= '-desktop-wallpapers';
		}

		$num = 0;
		$max = $this->getInput('m') ?: 14;
		$lastpage = 1;

		for($page = 1; $page <= $lastpage; $page++) {
			$link = self::URI . $category . '/page/' . $page;
			$html = getSimpleHTMLDOM($link)
				or returnServerError('No results for this query.');

			if($page === 1) {
				preg_match('/page\/(\d+)$/', $html->find('.pagination a', -2)->href, $matches);
				$lastpage = min($matches[1], ceil($max / 14));
			}

			$html = defaultLinkTo($html, self::URI);

			foreach($html->find('.wallpapers .wall a') as $element) {
				$thumbnail = $element->find('img', 0);

				$search = array(self::URI, 'wallpapers.html');
				$replace = array(self::URI . 'download/', $this->getInput('r') . '.jpg');

				$item = array();
				$item['uri'] = str_replace($search, $replace, $element->href);

				$item['timestamp'] = time();
				$item['title'] = $element->find('em1', 0)->text();
				$item['content'] = $item['title']
				. '<br><a href="'
				. $item['uri']
				. '"><img src="'
				. $thumbnail->src
				. '" /></a>';

				$item['enclosures'] = array($item['uri']);
				$this->items[] = $item;

				$num++;
				if ($num >= $max)
					break 2;
			}
		}
	}

	public function getName(){
		if(!is_null($this->getInput('c')) && !is_null($this->getInput('r'))) {
			return 'HDWallpapers - '
			. str_replace(array('__', '_'), array(' & ', ' '), $this->getInput('c'))
			. ' ['
			. $this->getInput('r')
			. ']';
		}

		return parent::getName();
	}
}
