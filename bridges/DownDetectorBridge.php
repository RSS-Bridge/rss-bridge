<?php
class DownDetectorBridge extends BridgeAbstract {
	const MAINTAINER = 'teromene';
	const NAME = 'DownDetector Bridge';
	const URI = 'https://downdetector.com/';
	const DESCRIPTION = 'Returns most recent downtimes from DownDetector';
	const CACHE_TIMEOUT = 300; // 5 min

	const PARAMETERS = array(
		'All Websites' => array(
			'country' => array(
				'type' => 'list',
				'name' => 'Country',
				'values' => array(
					'Argentina' => 'https://downdetector.com.ar',
					'Australia' => 'https://downdetector.com.au',
					'België' => 'https://allestoringen.be',
					'Brasil' => 'https://downdetector.com.br',
					'Canada' => 'https://downdetector.ca',
					'Chile' => 'https://downdetector.cl',
					'Colombia' => 'https://downdetector.com.co',
					'Danmark' => 'https://downdetector.dk',
					'Deutschland' => 'https://allestörungen.de',
					'Ecuador' => 'https://downdetector.ec',
					'España' => 'https://downdetector.es',
					'France' => 'https://downdetector.fr',
					'Hong Kong' => 'https://downdetector.hk',
					'Hrvatska' => 'https://downdetector.hr',
					'India' => 'https://downdetector.in',
					'Indonesia' => 'https://downdetector.id',
					'Ireland' => 'https://downdetector.ie',
					'Italia' => 'https://downdetector.it',
					'Magyarország' => 'https://downdetector.hu',
					'Malaysia' => 'https://downdetector.my',
					'México' => 'https://downdetector.mx',
					'Nederland' => 'https://allestoringen.nl',
					'New Zealand' => 'https://downdetector.co.nz',
					'Norge' => 'https://downdetector.no',
					'Pakistan' => 'https://downdetector.pk',
					'Perú' => 'https://downdetector.pe',
					'Pilipinas' => 'https://downdetector.ph',
					'Polska' => 'https://downdetector.pl',
					'Portugal' => 'https://downdetector.pt',
					'România' => 'https://downdetector.ro',
					'Schweiz' => 'https://allestörungen.ch',
					'Singapore' => 'https://downdetector.sg',
					'Slovensko' => 'https://downdetector.sk',
					'South Africa' => 'https://downdetector.co.za',
					'Suomi' => 'https://downdetector.fi',
					'Sverige' => 'https://downdetector.se',
					'Türkiye' => 'https://downdetector.web.tr',
					'UAE' => 'https://downdetector.ae',
					'UK' => 'https://downdetector.co.uk',
					'United States' => 'https://downdetector.com',
					'Österreich' => 'https://allestörungen.at',
					'Česko' => 'https://downdetector.cz',
					'Ελλάς' => 'https://downdetector.gr',
					'Россия' => 'https://downdetector.ru',
					'日本' => 'https://downdetector.jp'
				)
			)
		),
	);

	public function collectData(){

		if($this->queriedContext == 'All Websites') {
			$html = getSimpleHTMLDOM($this->getURI() . '/archive/')
				or returnClientError('Could not request website!.');

			$html = defaultLinkTo($html, $this->getURI());

			$table = $html->find('table.table-striped', 0);

			if ($table) {
				foreach ($table->find('tr') as $event) {
					$td = $event->find('td', 0);

					if (is_null($td)) {
						continue;
					}

					$item['uri'] = $event->find('td', 0)->find('a', 0)->href;
					$item['title'] = $event->find('td', 0)->find('a', 0)->plaintext
						. '(' . trim($event->find('td', 1)->plaintext) . ' ' . trim($event->find('td', 2)->plaintext) . ')';
					$item['content'] = 'User reports indicate problems at' . $event->find('td', 0)->find('a', 0)->plaintext . ' since ' . $event->find('td', 2)->plaintext;  
					$item['timestamp'] = $this->formatDate(
						trim($event->find('td', 1)->plaintext),
						trim($event->find('td', 2)->plaintext)
					);

					$this->items[] = $item;
				}
			}
		}
	}

	public function getURI() {
		if($this->getInput('country')) {
			return $this->getInput('country');
		} else {
			return self::URI;
		}
	}

	public function getName() {
		if($this->getInput('country')) {
			$parameters = $this->getParameters();
			$countryValues = array_flip($parameters['All Websites']['country']['values']);
			$country = $countryValues[$this->getInput('country')];

			return $country . ' - DownDetector';
		}

		return self::NAME;
	}

	private function formatDate($date, $time) {
		$parameters = $this->getParameters();
		$countryValues = array_flip($parameters['All Websites']['country']['values']);
		$country = $countryValues[$this->getInput('country')];

		switch($country) {
			case 'Australia':
			case 'UK':
				$date = DateTime::createFromFormat('d/m/Y', $date);
				return $date->format('Y-m-d') . $time;
			case 'Brasil':
			case 'Chile':
			case 'Colombia':
			case 'Ecuador':
			case 'España':
			case 'Italia':
			case 'Perú':
			case 'Portugal':
				$date = DateTime::createFromFormat('d/m/Y', $date);
				return $date->format('Y-m-d') . $time;
			case 'Magyarország':
				$date = DateTime::createFromFormat('Y.m.d.', $date);
				return $date->format('Y-m-d') . $time;
			default:
				return $date . $time;
		}
	}
}
