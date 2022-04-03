<?php

class MixCloudBridge extends BridgeAbstract {

	const MAINTAINER = 'Alexis CHEMEL';
	const NAME = 'MixCloud';
	const URI = 'https://www.mixcloud.com';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns latest musics on user stream';

	const PARAMETERS = array(array(
		'u' => array(
			'name' => 'username',
			'required' => true,
			'exampleValue' => 'DJJazzyJeff',
		)
	));

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return 'MixCloud - ' . $this->getInput('u');
		}

		return parent::getName();
	}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . '/' . $this->getInput('u'));

		foreach($html->find('section.card') as $element) {

			$item = array();

			$item['uri'] = self::URI . $element->find('hgroup.card-title h1 a', 0)->getAttribute('href');
			$item['title'] = html_entity_decode(
				$element->find('hgroup.card-title h1 a span', 0)->getAttribute('title'),
				ENT_QUOTES
			);

			$image = $element->find('a.album-art img', 0);

			if($image) {
				$item['content'] = '<img src="' . $image->getAttribute('src') . '" />';
			}

			$item['author'] = trim($element->find('hgroup.card-title h2 a', 0)->innertext);

			$this->items[] = $item;
		}
	}
}
