<?php
class OtrkeyFinderBridge extends BridgeAbstract {
	const MAINTAINER = 'mibe';
	const NAME = 'OtrkeyFinder';
	const URI = 'https://otrkeyfinder.com';
	const URI_TEMPLATE = 'https://otrkeyfinder.com/en/?search=%s&order=&page=%d';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns the newest .otrkey files matching the search criteria.';
	const PARAMETERS = array(
		array(
			'searchterm' => array(
				'name' => 'Search term',
				'exampleValue' => 'Terminator',
				'defaultValue' => '',
			),
			'station' => array(
				'name' => 'Station name',
				'exampleValue' => 'ARD',
				'defaultValue' => '',
			),
			'type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'values' => array(
					'any' => '',
					'Detail' => array(
						'HD' => 'HD.avi',
						'AC3' => 'HD.ac3',
						'HD &amp; AC3' => 'HD.',
						'HQ' => 'HQ.avi',
						'AVI' => 'g.avi',	// 'g.' to exclude HD.avi and HQ.avi
						'MP4' => '.mp4',
					),
				),
			),
			'minTime' => array(
				'name' => 'Min. running time',
				'type' => 'number',
				'title' => '',
				'exampleValue' => '90',
				'defaultValue' => '0',
			),
			'maxTime' => array(
				'name' => 'Max. running time',
				'type' => 'number',
				'exampleValue' => '120',
				'defaultValue' => '0',
			),
			'pages' => array(
				'name' => 'Number of pages',
				'type' => 'number',
				'title' => 'Specifies the number of pages to fetch. Increase this value if you get an empty feed.',
				'exampleValue' => '5',
				'defaultValue' => '5',
			),
		)
	);
	// Example: Terminator_20.04.13_02-25_sf2_100_TVOON_DE.mpg.avi.otrkey
	const FILENAME_REGEX = '/_(\d+)_TVOON_DE\.mpg\..+\.otrkey/';
	const CONTENT_TEMPLATE = '<ul>%s</ul>';
	const MIRROR_TEMPLATE = '<li><a href="https://otrkeyfinder.com%s">%s</a></li>';

	public function collectData()
	{
		$pages = $this->getInput('pages');
		
		for($page = 1; $page <= $pages; $page++)
		{
			$uri = $this->buildUri($page);
			
			$html = getSimpleHTMLDOMCached($uri, self::CACHE_TIMEOUT)
				or returnServerError('Could not request ' . $uri);
				
			$keys = $html->find('div.otrkey');
			
			foreach($keys as $key)
			{
				$temp = $this->buildItem($key);
				
				if ($temp != null)
					$this->items[] = $temp;
			}
			
			// Sleep for 0.5 seconds to don't hammer the server.
			usleep(500000);
		}
	}
	
	private function buildUri($page)
	{
		$searchterm = $this->getInput('searchterm');
		$station = $this->getInput('station');
		$type = $this->getInput('type');

		$search = implode(' ', array($searchterm, $station, $type));
		$search = trim($search);
		$search = urlencode($search);
		
		return sprintf(self::URI_TEMPLATE, $search, $page);
	}
	
	private function buildItem(simple_html_dom_node $node)
	{
		$file = $this->getFilename($node);
		
		if ($file == null)
			return null;

		$minTime = $this->getInput('minTime');
		$maxTime = $this->getInput('maxTime');
		
		// Do we need to check the running time?
		if ($minTime != 0 || $maxTime != 0)
		{
			if ($maxTime > 0 && $maxTime < $minTime)
				returnClientError('The minimum running time must be less than the maximum running time.');
				
			preg_match(self::FILENAME_REGEX, $file, $matches);
			
			if (!isset($matches[1]))
				return null;
			
			$time = (integer)$matches[1];
			
			if ($minTime > 0 && $minTime > $time)
				return null;
				
			if ($maxTime > 0 && $maxTime < $time)
				return null;
		}
		
		$item = array();
		$item['title'] = $file;
		$item['uri'] = sprintf(self::URI_TEMPLATE, $file, 1);
		
		$content = $this->buildContent($node);
		
		if ($content != null)
			$item['content'] = $content;
		
		return $item;
	}
	
	private function getFilename(simple_html_dom_node $node)
	{
		$a = $node->find('a.otrkey', 0);
		
		if (!isset($a->href))
			return null;
			
		$href = $a->href;
		return substr($href, strpos($href, '=') + 1);
	}
	
	private function buildContent(simple_html_dom_node $node)
	{
		$mirrors = $node->find('div.mirror');
		$list = '';
		
		foreach($mirrors as $mirror)
		{
			$anchor = $mirror->find('a', 0);
			$list .= sprintf(self::MIRROR_TEMPLATE, $anchor->href, $anchor->innertext);
		}
		
		return sprintf(self::CONTENT_TEMPLATE, $list);
	}
}
