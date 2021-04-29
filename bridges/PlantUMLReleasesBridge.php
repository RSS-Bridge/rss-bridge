<?php

/**
 * PlantUML releases bridge showing latest releases content
 * @author nicolas-delsaux
 *
 */
class PlantUMLReleasesBridge extends BridgeAbstract
{
	const MAINTAINER = 'Riduidel';

	const NAME = 'PlantUML Releases';

	const AUTHOR = 'PlantUML team';

	// URI is no more valid, since we can address the whole gq galaxy
	const URI = 'http://plantuml.com/fr/changes';

	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'PlantUML releases bridge, showing for each release the changelog';

	const DEFAULT_DOMAIN = 'plantuml.com';

	const PARAMETERS = array( array(
	));

	const REPLACED_ATTRIBUTES = array(
		'href' => 'href',
		'src' => 'src',
		'data-original' => 'src'
	);

	private function getDomain() {
		$domain = $this->getInput('domain');
		if (empty($domain))
			$domain = self::DEFAULT_DOMAIN;
		if (strpos($domain, '://') === false)
			$domain = 'https://' . $domain;
		return $domain;
	}

	public function getURI()
	{
		return self::URI;
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request ' . $this->getURI());

		// Since GQ don't want simple class scrapping, let's do it the hard way and ... discover content !
		$main = $html->find('div[id=root]', 0);
		foreach ($main->find('h2') as $release) {
			$item = array();
			$item['author'] = self::AUTHOR;
			$release_text = $release->innertext;
			if (preg_match('/(.+) \((.*)\)/', $release_text, $matches)) {
				$item['title'] = $matches[1];
				// And now, build the date from the date text
				$item['timestamp'] = strtotime($matches[2]);
			}
			$item['uri'] = $this->getURI();
			$item['content'] = $release->next_sibling ();
			$this->items[] = $item;
		}
	}
}
