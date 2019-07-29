<?php
class AtmoNouvelleAquitaineBridge extends BridgeAbstract {

	const NAME = 'Atmo Nouvelle Aquitaine';
	const URI = 'https://www.atmo-nouvelleaquitaine.org/monair/commune/33063';
	const DESCRIPTION = 'Fetches the latest air polution of Bordeaux from Atmo Nouvelle Aquitaine';
	const MAINTAINER = 'floviolleau';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 7200;

	private $dom;

	private function getClosest($search, $arr) {
		$closest = null;
		foreach ($arr as $key => $value) {
			if ($closest === null || abs((int)$search - $closest) > abs((int)$key - (int)$search)) {
				$closest = (int)$key;
			}
		}
		return $arr[$closest];
	}

	public function collectData() {
		$uri = self::URI;

		$html = getSimpleHTMLDOM($uri)
				or returnServerError('Could not request ' . $uri);

		$this->dom = $html->find('#block-system-main .city-prevision-map', 0);

		$message = $this->getIndexMessage() . ' ' . $this->getQualityMessage();
		$message .= ' ' . $this->getTomorrowTrendIndexMessage() . ' ' . $this->getTomorrowTrendQualityMessage();

		$item['uri'] = $uri;
		$today = date('d/m/Y');
		$item['title'] = "Bulletin de l'air du $today pour la région Nouvelle Aquitaine.";
		$item['title'] .= ' Retrouvez plus d\'informations en allant sur atmo-nouvelleaquitaine.org #QualiteAir.';
		$item['author'] = 'floviolleau';
		$item['content'] = $message;

		$this->items[] = $item;
	}

	private function getIndex() {
		$index = $this->dom->find('.indice', 0)->innertext;

		if ($index == 'XX') {
			return -1;
		}

		return $index;
	}

	private function getMaxIndexText() {
		// will return '/100'
		return $this->dom->find('.pourcent', 0)->innertext;
	}

	private function getQualityText($index, $indexes) {
		if ($index == -1) {
			if (array_key_exists('no-available', $indexes)) {
				return $indexes['no-available'];
			}

			return 'Aucune donnée';
		}

		return $this->getClosest($index, $indexes);
	}

	private function getLegendIndexes() {
		$rawIndexes = $this->dom->find('.prevision-legend .prevision-legend-label');
		$indexes = [];
		for ($i = 0; $i < count($rawIndexes); $i++) {
			if ($rawIndexes[$i]->hasAttribute('data-color')) {
				$indexes[$rawIndexes[$i]->getAttribute('data-color')] = $rawIndexes[$i]->innertext;
			}
		}

		return $indexes;
	}

	private function getTomorrowTrendIndex() {
		$tomorrowTrendDomNode = $this->dom
			->find('.day-controls.raster-controls .list-raster-controls .raster-control', 2);
		
		if ($tomorrowTrendDomNode) {
                        $tomorrowTrendIndexNode = $tomorrowTrendDomNode->find('.raster-control-link', 0);
                }

                if ($tomorrowTrendIndexNode && $tomorrowTrendIndexNode->hasAttribute('data-index')) {
                        $tomorrowTrendIndex = $tomorrowTrendIndexNode->getAttribute('data-index');
                } else {
                        return -1
                }

		return $tomorrowTrendIndex;
	}

	private function getTomorrowTrendQualityText($trendIndex, $indexes) {
		if ($trendIndex == -1) {
			if (array_key_exists('no-available', $indexes)) {
				return $indexes['no-available'];
			}

			return 'Aucune donnée';
		}

		return $this->getClosest($trendIndex, $indexes);
	}

	private function getIndexMessage() {
		$index = $this->getIndex();
		$maxIndexText = $this->getMaxIndexText();

		if ($index == -1) {
			return 'Aucune donnée pour l\'indice.';
		}

		return "L'indice d'aujourd'hui est $index$maxIndexText.";
	}

	private function getQualityMessage() {
		$index = $index = $this->getIndex();
		$indexes = $this->getLegendIndexes();
		$quality = $this->getQualityText($index, $indexes);

		if ($index == -1) {
			return 'Aucune donnée pour la qualité de l\'air.';
		}

		return "La qualité de l'air est $quality.";
	}

	private function getTomorrowTrendIndexMessage() {
		$trendIndex = $this->getTomorrowTrendIndex();
		$maxIndexText = $this->getMaxIndexText();

		if ($trendIndex == -1) {
			return 'Aucune donnée pour l\'indice prévu demain.';
		}

		return "L'indice prévu pour demain est $trendIndex$maxIndexText.";
	}

	private function getTomorrowTrendQualityMessage() {
		$trendIndex = $this->getTomorrowTrendIndex();
		$indexes = $this->getLegendIndexes();
		$trendQuality = $this->getTomorrowTrendQualityText($trendIndex, $indexes);

		if ($trendIndex == -1) {
			return 'Aucune donnée pour la qualité de l\'air de demain.';
		}
		return "La qualite de l'air pour demain sera $trendQuality.";
	}
}
