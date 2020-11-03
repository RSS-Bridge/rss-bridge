<?php
class AllocineFRBridge extends BridgeAbstract {

	const MAINTAINER = 'superbaillot.net';
	const NAME = 'Allo Cine Bridge';
	const CACHE_TIMEOUT = 25200; // 7h
	const URI = 'http://www.allocine.fr/';
	const DESCRIPTION = 'Bridge for allocine.fr';
	const PARAMETERS = array( array(
		'category' => array(
			'name' => 'Emission',
			'type' => 'list',
			'title' => 'Sélectionner l\'emission',
			'values' => array(
				'Faux Raccord' => 'faux-raccord',
				'Fanzone' => 'fanzone',
				'Game In Ciné' => 'game-in-cine',
				'Pour la faire courte' => 'pour-la-faire-courte',
				'Home Cinéma' => 'home-cinema',
				'PILS - Par Ici Les Sorties' => 'pils-par-ici-les-sorties',
				'AlloCiné : l\'émission, sur LeStream' => 'allocine-lemission-sur-lestream',
				'Give Me Five' => 'give-me-five',
				'Aviez-vous remarqué ?' => 'aviez-vous-remarque',
				'Et paf, il est mort' => 'et-paf-il-est-mort',
				'The Big Fan Theory' => 'the-big-fan-theory',
				'Clichés' => 'cliches',
				'Complètement...' => 'completement',
				'#Fun Facts' => 'fun-facts',
				'Origin Story' => 'origin-story',
			)
		)
	));

	public function getURI(){
		if(!is_null($this->getInput('category'))) {

			$categories = array(
				'faux-raccord' => 'video/programme-12284/saison-37054/',
				'fanzone' => 'video/programme-12298/saison-37059/',
				'game-in-cine' => 'video/programme-12288/saison-22971/',
				'pour-la-faire-courte' => 'video/programme-20960/saison-29678/',
				'home-cinema' => 'video/programme-12287/saison-34703/',
				'pils-par-ici-les-sorties' => 'video/programme-25789/saison-37253/',
				'allocine-lemission-sur-lestream' => 'video/programme-25123/saison-36067/',
				'give-me-five' => 'video/programme-21919/saison-34518/',
				'aviez-vous-remarque' => 'video/programme-19518/saison-37084/',
				'et-paf-il-est-mort' => 'video/programme-25113/saison-36657/',
				'the-big-fan-theory' => 'video/programme-20403/saison-37419/',
				'cliches' => 'video/programme-24834/saison-35591/',
				'completement' => 'video/programme-23859/saison-34102/',
				'fun-facts' => 'video/programme-23040/saison-32686/',
				'origin-story' => 'video/programme-25667/saison-37041/'
			);

			$category = $this->getInput('category');
			if(array_key_exists($category, $categories)) {
				return static::URI . $categories[$category];
			} else {
				returnClientError('Emission inconnue');
			}
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('category'))) {
			return self::NAME . ' : '
				. array_search(
					$this->getInput('category'),
					self::PARAMETERS[$this->queriedContext]['category']['values']
				);
		}

		return parent::getName();
	}

	public function collectData(){

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request ' . $this->getURI() . ' !');

		$category = array_search(
				$this->getInput('category'),
				self::PARAMETERS[$this->queriedContext]['category']['values']
			);

		foreach($html->find('div[class=gd-col-left]', 0)->find('div[class*=video-card]') as $element) {
			$item = array();

			$title = $element->find('a[class*=meta-title-link]', 0);
			$content = trim($element->outertext);

			// Replace image 'src' with the one in 'data-src'
			$content = preg_replace('@src="data:image/gif;base64,[A-Za-z0-9+\/]*"@', '', $content);
			$content = preg_replace('@data-src=@', 'src=', $content);

			// Remove date in the content to prevent content update while the video is getting older
			$content = preg_replace('@<div class="meta-sub light">.*<span>[^<]*</span>[^<]*</div>@', '', $content);

			$item['content'] = $content;
			$item['title'] = trim($title->innertext);
			$item['uri'] = static::URI . substr($title->href, 1);
			$this->items[] = $item;
		}
	}
}
