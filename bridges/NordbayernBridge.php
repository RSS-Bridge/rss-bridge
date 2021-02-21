<?php
ini_set('max_execution_time', '300');
class NordbayernBridge extends BridgeAbstract {

	const MAINTAINER = 'schabi.org';
	const NAME = 'Nordbayern Bridge';
	const CACHE_TIMEOUT = 3600;
	const URI = 'https://www.nordbayern.de';
	const DESCRIPTION = 'Bridge for Bavarian reginoal news site nordbayern.de';
	const PARAMETERS = array( array(
		'region' => array(
			'name' => 'region',
			'type' => 'list',
			'exampleValue' => 'Nürnberg',
			'title' => 'Select a region',
			'values' => array(
				'Nürnberg' => 'nuernberg',
				'Fürth' => 'fuerth',
				'Altdorf' => 'altdorf',
				'Ansbach' => 'ansbach',
				'Bad Windsheim' => 'bad-windsheim',
				'Bamberg' => 'bamberg',
				'Dinkelsbühl/Feuchtwangen' => 'dinkelsbuehl-feuchtwangen',
				'Feucht' => 'feucht',
				'Forchheim' => 'forchheim',
				'Gunzenhausen' => 'gunzenhausen',
				'Hersbruck' => 'hersbruck',
				'Herzogenaurach' => 'herzogenaurach',
				'Hilpoltstein' => 'hilpoltstein',
				'Höchstadt' => 'hoechstadt',
				'Lauf' => 'lauf',
				'Neumarkt' => 'neumarkt',
				'Neustadt/Aisch' => 'neustadt-aisch',
				'Pegnitz' => 'pegnitz',
				'Roth' => 'roth',
				'Rothenburg o.d.T.' => 'rothenburg-o-d-t',
				'Schwabach' => 'schwabach',
				'Treuchtlingen' => 'treuchtlingen',
				'Weißenburg' => 'weissenburg'
			)
		),
		'policeReports' => array(
			'name' => 'Police Reports',
			'type' => 'checkbox',
			'exampleValue' => 'checked',
			'title' => 'Read Police Reports',
		)
	));

	private function getImageUrlFromScript($script) {
		preg_match(
			"#src=\\\\'(https:[-:\\.\\\\/a-zA-Z0-9%_]*\\.(jpg|JPG))#",
			$script->innertext,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if(isset($matches[1][0])) {
			return stripcslashes($matches[1][0]) . '?w=800';
		}
		return null;
	}

	private function handleArticle($link) {
		$item = array();
		$article = getSimpleHTMLDOM($link);
		$content = $article->find('div[class*=article-content]', 0);
		$item['uri'] = $link;
		$item['title'] = $article->find('h1', 0)->innertext;
		$item['content'] = '';

		//first get image from block/modul
		$figure = $article->find('figure[class*=panorama]', 0);
		if($figure !== null) {
			$imgUrl = self::getImageUrlFromScript($figure->find('script', 0));
			if($imgUrl === null) {
				$imgUrl = self::getImageUrlFromScript($figure->find('script', 1));
			}
			$item['content'] .= '<img src="' . $imgUrl . '">';
		}

		// get regular paragraphs
		foreach($content->children() as $child) {
			if($child->tag === 'p') {
				$item['content'] .= $child;
			}
		}

		//get image divs
		foreach($content->find('div[class*=article-slideshow]') as $slides) {
			foreach($slides->children() as $child) {
				switch($child->tag) {
					case 'p':
						$item['content'] .= $child;
						break;
					case 'h5':
						$item['content'] .= '<h5><a href="'
							. self::URI . $child->find('a', 0)->href . '">' . $child->plaintext . '</a></h5>';
						break;
					case 'a':
						$url = self::getImageUrlFromScript($child->find('script', 0));
						$item['content'] .= '<img src="' . $url . '">';
						break;
				}
			}
		}
		$this->items[] = $item;
		$article->clear();
	}

	private function handleNewsblock($listSite, $readPoliceReports) {
		$newsBlocks = $listSite->find('section[class*=newsblock]');
		$regionalNewsBlock = $newsBlocks[0];
		$policeBlock = $newsBlocks[1];
		foreach($regionalNewsBlock->find('h2') as $headline) {
			self::handleArticle(self::URI . $headline->find('a', 0)->href);
		}
		if($readPoliceReports === true) {
			foreach($policeBlock->find('h2') as $headline) {
				self::handleArticle(self::URI . $headline->find('a', 0)->href);
			}
		}
	}

	public function collectData() {
		$item = array();
		$region = $this->getInput('region');
		$listSite = getSimpleHTMLDOM(self::URI . '/region/' . $region);

		self::handleNewsblock($listSite, $this->getInput('policeReports'));
	}
}
