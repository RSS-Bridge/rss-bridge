<?php
class UberNewsroomBridge extends BridgeAbstract {
	const NAME = 'Uber Newsroom Bridge';
	const URI = 'https://www.uber.com';
	const URI_API_DATA = 'https://newsroomapi.uber.com/wp-json/newsroom/v1/data?locale=';
	const URI_API_POST = 'https://newsroomapi.uber.com/wp-json/wp/v2/posts/';
	const DESCRIPTION = 'Returns news posts';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'region' => array(
			'name' => 'Region',
			'type' => 'list',
			'values' => array(
				'Africa' => array(
					'Egypt' => 'en-EG',
					'Ghana' => 'en-GH',
					'Kenya' => 'en-KE',
					'Nigeria' => 'en-NG',
					'South Africa' => 'en-ZA',
					'Tanzania' => 'en-TZ',
					'Uganda' => 'en-UG',
				),
				'Asia' => array(
					'Bangladesh' => 'en-BD',
					'Hong Kong' => 'en-HK',
					'India' => 'en-IN',
					'Japan' => 'ja-JP',
					'Korea' => 'en-KR',
					'Macau' => 'en-MO',
					'Sri Lanka' => 'en-LK',
					'Taiwan' => 'en-TW',
				),
				'Central America' => array(
					'Costa Rica' => 'es-CR',
					'Dominican Republic' => 'es-DO',
					'El Salvador' => 'es-SV',
					'Guatemala' => 'es-GT',
					'Honduras' => 'en-HN',
					'Mexico' => 'es-MX',
					'Nicaragua' => 'es-NI',
					'Panama' => 'es-PA',
					'Puerto Rico' => 'en-PR',
				),
				'Europe' => array(
					'Austria' => 'de-AT',
					'Azerbaijan' => 'az',
					'Belarus' => 'ru-BY',
					'Belgium' => 'en-BE',
					'Bulgaria' => 'en-BG',
					'Croatia' => 'hr',
					'Czech Republic' => 'cs-CZ',
					'Denmark' => 'en-DK',
					'Estonia' => 'en-EE',
					'Finland' => 'en-FI',
					'France' => 'en-FR',
					'Germany' => 'en-DE',
					'Greece' => 'en-GR',
					'Hungary' => 'en-HU',
					'Ireland' => 'en-IE',
					'Italy' => 'en-IT',
					'Kazakhstan' => 'ru-KZ',
					'Lithuania' => 'en-LT',
					'Netherlands' => 'en-NL',
					'Norway' => 'en-NO',
					'Poland' => 'pl',
					'Portugal' => 'en-PT',
					'Romania' => 'en-RO',
					'Russia' => 'ru',
					'Slovakia' => 'sk',
					'Spain' => 'es-ES',
					'Sweden' => 'en-SE',
					'Switzerland' => 'en-CH',
					'Turkey' => 'en-TR',
					'Ukraine' => 'uk-UA',
					'United Kingdom' => 'en-GB',
				),
				'Middle East' => array(
					'Bahrain' => 'en-BH',
					'Israel' => 'en-IL',
					'Jordan' => 'en-JO',
					'Lebanon' => 'en-LB',
					'Pakistan' => 'en-PK',
					'Qatar' => 'en-QA',
					'Saudi Arabia' => 'en-SA',
					'United Arab Emirates' => 'en-AE',
				),
				'North America' => array(
					'Canada' => 'en-CA',
					'United States' => 'en-US',
				),
				'Pacific' => array(
					'Australia' => 'en-AU',
					'New Zealand' => 'en-NZ',
				),
				'South America' => array(
					'Argentina' => 'es-AR',
					'Bolivia' => 'es-BO',
					'Brazil' => 'pt-BR',
					'Chile' => 'es-CL',
					'Colombia' => 'es-CO',
					'Ecuador' => 'es-EC',
					'Paraguay' => 'en-PY',
					'Peru' => 'es-PE',
					'Trinidad & Tobago' => 'en-TT',
					'Uruguay' => 'es-UY',
					'Venezuela' => 'en-VE',
				),
			),
			'defaultValue' => 'en-US',
		)
	));

	const CACHE_TIMEOUT = 3600;

	private array $regions = [
		'en-EG' => 'Egypt',
		'en-GH' => 'Ghana',
		'en-KE' => 'Kenya',
		'en-NG' => 'Nigeria',
		'en-ZA' => 'South Africa',
		'en-TZ' => 'Tanzania',
		'en-UG' => 'Uganda',
		'en-BD' => 'Bangladesh',
		'en-HK' => 'Hong Kong',
		'en-IN' => 'India',
		'ja-JP' => 'Japan',
		'en-KR' => 'Korea',
		'en-MO' => 'Macau',
		'en-LK' => 'Sri Lanka',
		'en-TW' => 'Taiwan',
		'es-CR' => 'Costa Rica',
		'es-DO' => 'Dominican Republic',
		'es-SV' => 'El Salvador',
		'es-GT' => 'Guatemala',
		'en-HN' => 'Honduras',
		'es-MX' => 'Mexico',
		'es-NI' => 'Nicaragua',
		'es-PA' => 'Panama',
		'en-PR' => 'Puerto Rico',
		'de-AT' => 'Austria',
		'az' => 'Azerbaijan',
		'ru-BY' => 'Belarus',
		'en-BE' => 'Belgium',
		'en-BG' => 'Bulgaria',
		'hr' => 'Croatia',
		'cs-CZ' => 'Czech Republic',
		'en-DK' => 'Denmark',
		'en-EE' => 'Estonia',
		'en-FI' => 'Finland',
		'en-FR' => 'France',
		'en-DE' => 'Germany',
		'en-GR' => 'Greece',
		'en-HU' => 'Hungary',
		'en-IE' => 'Ireland',
		'en-IT' => 'Italy',
		'ru-KZ' => 'Kazakhstan',
		'en-LT' => 'Lithuania',
		'en-NL' => 'Netherlands',
		'en-NO' => 'Norway',
		'pl' => 'Poland',
		'en-PT' => 'Portugal',
		'en-RO' => 'Romania',
		'ru' => 'Russia',
		'sk' => 'Slovakia',
		'es-ES' => 'Spain',
		'en-SE' => 'Sweden',
		'en-CH' => 'Switzerland',
		'en-TR' => 'Turkey',
		'uk-UA' => 'Ukraine',
		'en-GB' => 'United Kingdom',
		'en-BH' => 'Bahrain',
		'en-IL' => 'Israel',
		'en-JO' => 'Jordan',
		'en-LB' => 'Lebanon',
		'en-PK' => 'Pakistan',
		'en-QA' => 'Qatar',
		'en-SA' => 'Saudi Arabia',
		'en-AE' => 'United Arab Emirates',
		'en-CA' => 'Canada',
		'en-US' => 'United States',
		'en-AU' => 'Australia',
		'en-NZ' => 'New Zealand',
		'es-AR' => 'Argentina',
		'es-BO' => 'Bolivia',
		'pt-BR' => 'Brazil',
		'es-CL' => 'Chile',
		'es-CO' => 'Colombia',
		'es-EC' => 'Ecuador',
		'en-PY' => 'Paraguay',
		'es-PE' => 'Peru',
		'en-TT' => 'Trinidad & Tobago',
		'es-UY' => 'Uruguay',
		'en-VE' => 'Venezuela',
	];

	public function collectData() {
		$json = getContents(self::URI_API_DATA . $this->getInput('region'));
		$data = json_decode($json);

		foreach ($data->articles as $article) {
			$json = getContents(self::URI_API_POST . $article->id);
			$post = json_decode($json);

			$item = array();
			$item['title'] = $post->title->rendered;
			$item['timestamp'] = $post->date;
			$item['uri'] = $post->link;
			$item['content'] = $post->content->rendered;

			$this->items[] = $item;
		}
	}

	public function getURI() {
		if (is_null($this->getInput('region')) === false && $this->getInput('region') !== 'all') {
			return self::URI . '/' . $this->getInput('region') . '/newsroom';
		}

		return parent::getURI() . '/newsroom';
	}

	public function getName() {
		if (is_null($this->getInput('region')) === false) {
			return $this->regions[$this->getInput('region')] . ' - Uber Newsroom';
		}

		return parent::getName();
	}
}
