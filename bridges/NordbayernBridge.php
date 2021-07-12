<?php
ini_set('max_execution_time', '300');
class NordbayernBridge extends BridgeAbstract {

	const MAINTAINER = 'schabi.org';
	const NAME = 'Nordbayern';
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
				'Treuchtlingen' => 'treuchtlingen',
				'Weißenburg' => 'weissenburg'
			)
		),
		'policeReports' => array(
			'name' => 'Police Reports',
			'type' => 'checkbox',
			'exampleValue' => 'checked',
			'title' => 'Include Police Reports',
		)
	));

	private function startsWith($string, $startString) {
		$len = strlen($startString);
		return (substr($string, 0, $len) === $startString);
	}

	private function contains($haystack, $needle) {
		return (strpos($haystack, $needle) !== false);
	}

	private function getUseFullContent($rawContent) {
		$content = '';
		foreach($rawContent->children as $element) {
			if($element->tag === 'p' || $element->tag === 'h3') {
				$content .= $element;
			}
			if($element->tag === 'main') {
				$content .= self::getUseFullContent($element->find('article', 0));
			}
			if($element->tag === 'header') {
				$content .= self::getUseFullContent($element);
			}
		}
		return $content;
	}

	private function handleArticle($link) {
		$item = array();
		$article = getSimpleHTMLDOM($link);
		defaultLinkTo($article, self::URI);

		$item['uri'] = $link;
		$item['title'] = $article->find('h2', 0)->innertext;
		$item['content'] = '';

		//first get images from content
		$pictures = $article->find('picture');
		if(!empty($pictures)) {
			$bannerUrl = $pictures[0]->find('img', 0)->src;
			$item['content'] .= '<img src="' . $bannerUrl . '">';
		}

		$content = $article->find('section[class*=article__richtext]', 0)
						   ->find('div', 0)->find('div', 0);
		$item['content'] .= self::getUseFullContent($content);

		for($i = 1; $i < count($pictures); $i++) {
			$imgUrl = $pictures[$i]->find('img', 0)->src;
			$item['content'] .= '<img src="' . $imgUrl . '">';
		}

		// exclude police reports if descired
		if($this->getInput('policeReports') ||
			!self::contains($item['content'], 'Hier geht es zu allen aktuellen Polizeimeldungen.')) {
			$this->items[] = $item;
		}

		$article->clear();
	}

	private function handleNewsblock($listSite) {
		$main = $listSite->find('main', 0);
		foreach($main->find('article') as $article) {
			self::handleArticle(self::URI . $article->find('a', 0)->href);
		}
	}

	public function collectData() {
		$item = array();
		$region = $this->getInput('region');
		if($region === 'rothenburg-o-d-t') {
			$region = 'rothenburg-ob-der-tauber';
		}
		$listSite = getSimpleHTMLDOM(self::URI . '/region/' . $region);

		self::handleNewsblock($listSite);
	}
}
