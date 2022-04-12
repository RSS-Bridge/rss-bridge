<?php

class PicalaBridge extends BridgeAbstract {
	const TYPES		 = array(
		'Actualités' => 'actualites',
		'Économie'   => 'economie',
		'Tests'		 => 'tests',
		'Pratique'	 => 'pratique',
	);
	const NAME		    = 'Picala Bridge';												    // Name of the Bridge (default: "Unnamed Bridge")
	const URI		    = 'https://www.picala.fr';										    // URI to the target website of the bridge (default: empty)
	const DESCRIPTION   = 'Dernière nouvelles du média indépendant sur le vélo électrique'; // A brief description of the Bridge (default: "No description provided")
	const MAINTAINER	= 'Chouchen';													    // Name of the maintainer, i.e. your name on GitHub (default: "No maintainer")
	const PARAMETERS	= array(															// (optional) Definition of additional parameters (default: empty)
		array(
			'type' => array(
				'name' => 'Type',
				'type' => 'list',
				'values' => self::TYPES,
			),
		),
	);
	// const CACHE_TIMEOUT // (optional) Defines the maximum duration for the cache in seconds (default: 3600)
	public function getURI() {
		if(!is_null($this->getInput('type'))) {
			return sprintf('%s/%s', static::URI, $this->getInput('type'));
		}

		return parent::getURI();
	}

	public function getIcon() {
		return 'https://picala-static.s3.amazonaws.com/static/img/favicon/favicon-32x32.png';
	}

	public function getDescription() {
		if(!is_null($this->getInput('type'))) {
			return sprintf('%s - %s', static::DESCRIPTION, array_search($this->getInput('type'), self::TYPES));
		}

		return parent::getDescription();
	}

	public function getName() {
		if(!is_null($this->getInput('type'))) {
			return sprintf('%s - %s', static::NAME, array_search($this->getInput('type'), self::TYPES));
		}

		return parent::getName();
	}

	public function collectData() {
		$fullhtml = getSimpleHTMLDOM($this->getURI());
		foreach($fullhtml->find('.list-container-category a') as $article) {
			$item = array();
			$item['uri'] = self::URI . $article->href;
			$item['title'] = $article->find('h2', 0)->plaintext;
			$item['content'] = $article->find('.teaser__text')->plainText;
			$this->items[] = $item;
		}
	}
}
