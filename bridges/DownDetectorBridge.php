<?php
class DownDetectorBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'DownDetector Bridge';
	const URI = 'https://downdetector.com/';
	const DESCRIPTION = 'Returns most recent downtimes from DownDetector';
	const CACHE_TIMEOUT = 300; // 5 min

	const PARAMETERS = array(
		array(
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
		)
	);

	const API_TOKEN = 'YW5kcm9pZF9hcGlfdXNlcl92MTpxTkRyenZSczY1bW1ESlk0ZVNIWmtobFY=';

	public function collectData(){

		$html = getSimpleHTMLDOM($this->getURI() . '/archive/')
			or returnClientError('Impossible to query website !.');

		$table = $html->find('table.table-striped', 0);

		$maxCount = 10;
		foreach ($table->find('tr') as $downEvent) {
			$downLink = $downEvent->find('td', 1)->find('a', 1);
			$item = $this->collectArticleData($downLink->getAttribute('href'));
			$this->items[] = $item;
			if($maxCount == 0) break;
			$maxCount -= 1;
		}
	}

	public function collectArticleData($link) {

		preg_match('/\/([0-9]{3,})/', $link, $matches);
		$eventId = $matches[1];

		$header = array(
			'Authorization: Basic ' . self::API_TOKEN
		);

		$article = getContents('https://downdetectorapi.com/v1/events/' . $eventId, $header)
			or returnServerError('Could not request ARTE.');
		$article_json = json_decode($article);
		//die($this->getURI() . $link);
		$item = array();
		$item['uri'] = $this->getURI() . $link;
		$item['id'] = $article_json->id;
		$item['title'] = $article_json->title;
		$item['content'] = $article_json->body;
		$item['timestamp'] = (new DateTime($article_json->started_at))->getTimestamp();
		return $item;

	}

	public function getURI() {
		if($this->getInput('country') !== null) {
			return $this->getInput('country');
		} else {
			return self::URI;
		}
	}
}
