<?php
class IndeedBridge extends BridgeAbstract {

	const NAME = 'Indeed';
	const URI = 'https://www.indeed.com/';
	const DESCRIPTION = 'Returns reviews and comments for a company of your choice';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 14400; // 4 hours

	const PARAMETERS = array(
		array(
			'c' => array(
				'name' => 'Company',
				'type' => 'text',
				'required' => true,
				'title' => 'Company name',
				'exampleValue' => 'GitHub',
			)
		),
		'global' => array(
			'language' => array(
				'name' => 'Language Code',
				'type' => 'list',
				'title' => 'Choose your language code',
				'defaultValue' => 'en-US',
				'values' => array(
					'es-AR' => 'es-AR',
					'de-AT' => 'de-AT',
					'en-AU' => 'en-AU',
					'nl-BE' => 'nl-BE',
					'fr-BE' => 'fr-BE',
					'pt-BR' => 'pt-BR',
					'en-CA' => 'en-CA',
					'fr-CA' => 'fr-CA',
					'de-CH' => 'de-CH',
					'fr-CH' => 'fr-CH',
					'es-CL' => 'es-CL',
					'zh-CN' => 'zh-CN',
					'es-CO' => 'es-CO',
					'de-DE' => 'de-DE',
					'es-ES' => 'es-ES',
					'fr-FR' => 'fr-FR',
					'en-GB' => 'en-GB',
					'en-HK' => 'en-HK',
					'en-IE' => 'en-IE',
					'en-IN' => 'en-IN',
					'it-IT' => 'it-IT',
					'ja-JP' => 'ja-JP',
					'ko-KR' => 'ko-KR',
					'es-MX' => 'es-MX',
					'nl-NL' => 'nl-NL',
					'pl-PL' => 'pl-PL',
					'en-SG' => 'en-SG',
					'en-US' => 'en-US',
					'en-ZA' => 'en-ZA',
					'en-AE' => 'en-AE',
					'da-DK' => 'da-DK',
					'in-ID' => 'in-ID',
					'en-MY' => 'en-MY',
					'es-PE' => 'es-PE',
					'en-PH' => 'en-PH',
					'en-PK' => 'en-PK',
					'ro-RO' => 'ro-RO',
					'ru-RU' => 'ru-RU',
					'tr-TR' => 'tr-TR',
					'zh-TW' => 'zh-TW',
					'vi-VN' => 'vi-VN',
					'en-VN' => 'en-VN',
					'ar-EG' => 'ar-EG',
					'fr-MA' => 'fr-MA',
					'en-NG' => 'en-NG',
				)
			),
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'title' => 'Maximum number of items to return',
				'exampleValue' => 20,
			)
		)
	);

	const SITES = array(
		'es-AR' => 'https://ar.indeed.com/',
		'de-AT' => 'https://at.indeed.com/',
		'en-AU' => 'https://au.indeed.com/',
		'nl-BE' => 'https://be.indeed.com/',
		'fr-BE' => 'https://emplois.be.indeed.com/',
		'pt-BR' => 'https://www.indeed.com.br/',
		'en-CA' => 'https://ca.indeed.com/',
		'fr-CA' => 'https://emplois.ca.indeed.com/',
		'de-CH' => 'https://www.indeed.ch/',
		'fr-CH' => 'https://emplois.indeed.ch/',
		'es-CL' => 'https://www.indeed.cl/',
		'zh-CN' => 'https://cn.indeed.com/',
		'es-CO' => 'https://co.indeed.com/',
		'de-DE' => 'https://de.indeed.com/',
		'es-ES' => 'https://www.indeed.es/',
		'fr-FR' => 'https://www.indeed.fr/',
		'en-GB' => 'https://www.indeed.co.uk/',
		'en-HK' => 'https://www.indeed.hk/',
		'en-IE' => 'https://ie.indeed.com/',
		'en-IN' => 'https://www.indeed.co.in/',
		'it-IT' => 'https://it.indeed.com/',
		'ja-JP' => 'https://jp.indeed.com/',
		'ko-KR' => 'https://kr.indeed.com/',
		'es-MX' => 'https://www.indeed.com.mx/',
		'nl-NL' => 'https://www.indeed.nl/',
		'pl-PL' => 'https://pl.indeed.com/',
		'en-SG' => 'https://www.indeed.com.sg/',
		'en-US' => 'https://www.indeed.com/',
		'en-ZA' => 'https://www.indeed.co.za/',
		'en-AE' => 'https://www.indeed.ae/',
		'da-DK' => 'https://dk.indeed.com/',
		'in-ID' => 'https://id.indeed.com/',
		'en-MY' => 'https://www.indeed.com.my/',
		'es-PE' => 'https://www.indeed.com.pe/',
		'en-PH' => 'https://www.indeed.com.ph/',
		'en-PK' => 'https://www.indeed.com.pk/',
		'ro-RO' => 'https://ro.indeed.com/',
		'ru-RU' => 'https://ru.indeed.com/',
		'tr-TR' => 'https://tr.indeed.com/',
		'zh-TW' => 'https://tw.indeed.com/',
		'vi-VN' => 'https://vn.indeed.com/',
		'en-VN' => 'https://jobs.vn.indeed.com/',
		'ar-EG' => 'https://eg.indeed.com/',
		'fr-MA' => 'https://ma.indeed.com/',
		'en-NG' => 'https://ng.indeed.com/',
	);

	private $title;

	public function collectData() {

		$url = $this->getURI();
		$limit = $this->getInput('limit') ?: 20;

		do {

			$html = getSimpleHTMLDOM($url)
				or returnServerError('Could not request ' . $url);

			$html = defaultLinkTo($html, $url);

			$this->title = $html->find('h1', 0)->innertext;

			// Use local translation of the word "Rating"
			$rating_local = $html->find('a[data-id="rating_desc"]', 0)->plaintext;

			foreach($html->find('#cmp-content [id^="cmp-review-"]') as $review) {
				$item = array();

				$rating = $review->find('.cmp-ratingNumber', 0)->plaintext;
				$title = $review->find('.cmp-review-title > span', 0)->plaintext;
				$comment = $this->beautifyComment($review->find('.cmp-review-content-container', 0));

				$item['uri'] = $review->find('.cmp-review-share-popup-item-link--copylink', 0)->href;
				$item['title'] = "{$rating_local} {$rating} / {$title}";
				$item['timestamp'] = $review->find('.cmp-review-date-created', 0)->plaintext;
				$item['author'] = $review->find('.cmp-reviewer', 0)->plaintext;
				$item['content'] = $comment;
				//$item['enclosures']
				$item['categories'][] = $review->find('.cmp-reviewer-job-location', 0)->plaintext;
				//$item['uid']

				$this->items[] = $item;

				if(count($this->items) >= $limit) {
					break;
				}
			}

			// Break if no more pages available.
			if($next = $html->find('a[data-tn-element="next-page"]', 0)) {
				$url = $next->href;
			} else {
				break;
			}

		} while(count($this->items) < $limit);

	}

	public function getURI() {
		if($this->getInput('language')
		&& $this->getInput('c')) {
			return self::SITES[$this->getInput('language')]
			. 'cmp/'
			. urlencode($this->getInput('c'))
			. '/reviews';
		}

		return parent::getURI();
	}

	public function getName() {
		return $this->title ?: parent::getName();
	}

	public function detectParameters($url) {
		/**
		 * Expected: https://<...>.indeed.<...>/cmp/<company>[/reviews][/...]
		 *
		 * Note that most users will be redirected to their localized version
		 * of the page, which adds the language code to the host. For example,
		 * "en.indeed.com" or "www.indeed.fr" (see link[rel="alternate"]). At
		 * least each of the sites have ".indeed." in the name.
		 */

		if(filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false
		|| stristr($url, '.indeed.') === false) {
			return null;
		}

		$url_components = parse_url($url);
		$path_segments = array_values(array_filter(explode('/', $url_components['path'])));

		if(count($path_segments) < 2 || $path_segments[0] !== 'cmp') {
			return null;
		}

		$language = array_search('https://' . $url_components['host'] . '/', self::SITES);
		if($language === false) {
			return null;
		}

		$limit = self::PARAMETERS['global']['limit']['defaultValue'] ?: 20;
		$company = $path_segments[1];

		return array(
			'c' => $company,
			'language' => $language,
			'limit' => $limit,
		);
	}

	private function beautifyComment($comment) {
		foreach($comment->find('.cmp-bold') as $bold) {
			$bold->tag = 'strong';
			$bold->removeClass('cmp-bold');
		}

		return $comment;
	}
}
