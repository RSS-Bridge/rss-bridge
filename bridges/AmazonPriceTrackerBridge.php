<?php

class AmazonPriceTrackerBridge extends BridgeAbstract {
	const MAINTAINER = 'captn3m0, sal0max';
	const NAME = 'Amazon Price Tracker';
	const URI = 'https://www.amazon.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Tracks price for a single product on Amazon';

	const PARAMETERS = array(
		array(
		'asin' => array(
			'name' 			=> 'ASIN',
			'required' 		=> true,
			'exampleValue' 	=> 'B071GB1VMQ',
			// https://stackoverflow.com/a/12827734
			'pattern'		=> 'B[\dA-Z]{9}|\d{9}(X|\d)',
		),
		'tld' => array(
			'name' => 'Country',
			'type' => 'list',
			'values' => array(
				'Australia' 	=> 'com.au',
				'Brazil' 		=> 'com.br',
				'Canada' 		=> 'ca',
				'China' 		=> 'cn',
				'France' 		=> 'fr',
				'Germany' 		=> 'de',
				'India' 		=> 'in',
				'Italy' 		=> 'it',
				'Japan' 		=> 'co.jp',
				'Mexico' 		=> 'com.mx',
				'Netherlands'	=> 'nl',
				'Spain' 		=> 'es',
				'Sweden' 		=> 'se',
				'United Kingdom'	=> 'co.uk',
				'United States'		=> 'com',
			),
			'defaultValue' => 'com',
		),
	));

	const PRICE_SELECTORS = array(
		'#priceblock_ourprice',
		'.priceBlockBuyingPriceString',
		'#newBuyBoxPrice',
		'#tp_price_block_total_price_ww',
		'span.offer-price',
		'.a-color-price',
	);

	protected $title;

	/**
	 * Generates domain name given a amazon TLD
	 */
	private function getDomainName() {
		return 'https://www.amazon.' . $this->getInput('tld');
	}

	/**
	 * Generates URI for a Amazon product page
	 */
	public function getURI() {
		if (!is_null($this->getInput('asin'))) {
			return $this->getDomainName() . '/dp/' . $this->getInput('asin');
		}
		return parent::getURI();
	}

	/**
	 * Scrapes the product title from the html page
	 * returns the default title if scraping fails
	 */
	private function getTitle($html) {
		$titleTag = $html->find('#productTitle', 0);

		if (!$titleTag) {
			return $this->getDefaultTitle();
		} else {
			return trim(html_entity_decode($titleTag->innertext, ENT_QUOTES));
		}
	}

	/**
	 * Title used by the feed if none could be found
	 */
	private function getDefaultTitle() {
		return 'Amazon.' . $this->getInput('tld') . ': ' . $this->getInput('asin');
	}

	/**
	 * Returns name for the feed
	 * Uses title (already scraped) if it has one
	 */
	public function getName() {
		if (isset($this->title)) {
			return $this->title;
		} else {
			return parent::getName();
		}
	}

	private function parseDynamicImage($attribute) {
		$json = json_decode(html_entity_decode($attribute), true);

		if ($json and count($json) > 0) {
			return array_keys($json)[0];
		}
	}

	/**
	 * Returns a generated image tag for the product
	 */
	private function getImage($html) {
		$imageSrc = $html->find('#main-image-container img', 0);

		if ($imageSrc) {
			$hiresImage = $imageSrc->getAttribute('data-old-hires');
			$dynamicImageAttribute = $imageSrc->getAttribute('data-a-dynamic-image');
			$image = $hiresImage ?: $this->parseDynamicImage($dynamicImageAttribute);
		}
		$image = $image ?: 'https://placekitten.com/200/300';

		return <<<EOT
<img width="300" style="max-width:300;max-height:300" src="$image" alt="{$this->title}" />
EOT;
	}

	/**
	 * Return \simple_html_dom object
	 * for the entire html of the product page
	 */
	private function getHtml() {
		$uri = $this->getURI();

		return getSimpleHTMLDOM($uri) ?: returnServerError('Could not request Amazon.');
	}

	private function scrapePriceFromMetrics($html) {
		$asinData = $html->find('#cerberus-data-metrics', 0);

		// <div id="cerberus-data-metrics" style="display: none;"
		// 	data-asin="B00WTHJ5SU" data-asin-price="14.99" data-asin-shipping="0"
		// 	data-asin-currency-code="USD" data-substitute-count="-1" ... />
		if ($asinData) {
			return array(
				'price' 	=> $asinData->getAttribute('data-asin-price'),
				'currency'	=> $asinData->getAttribute('data-asin-currency-code'),
				'shipping'	=> $asinData->getAttribute('data-asin-shipping')
			);
		}

		return false;
	}

	private function scrapePriceGeneric($html) {
		$priceDiv = null;

		foreach(self::PRICE_SELECTORS as $sel) {
			$priceDiv = $html->find($sel, 0);
			if ($priceDiv) {
				break;
			}
		}

		if (!$priceDiv) {
			return false;
		}

		$priceString = $priceDiv->plaintext;

		preg_match('/[\d.,]+/', $priceString, $matches);

		$price = $matches[0];
		$currency = trim(str_replace($price, '', $priceString), " \t\n\r\0\x0B\xC2\xA0");

		if ($price != null && $currency != null) {
			return array(
				'price' 	=> $price,
				'currency'	=> $currency,
				'shipping'	=> '0'
			);
		}

		return false;
	}

	/**
	 * Scrape method for Amazon product page
	 * @return [type] [description]
	 */
	public function collectData() {
		$html = $this->getHtml();
		$this->title = $this->getTitle($html);
		$imageTag = $this->getImage($html);

		$data = $this->scrapePriceFromMetrics($html) ?: $this->scrapePriceGeneric($html);

		$item = array(
			'title' 	=> $this->title,
			'uri' 		=> $this->getURI(),
			'content' 	=> "$imageTag<br/>Price: {$data['price']} {$data['currency']}",
			// This is to ensure that feed readers notice the price change
			'uid'		=> md5($data['price'])
		);

		if ($data['shipping'] !== '0') {
			$item['content'] .= "<br>Shipping: {$data['shipping']} {$data['currency']}</br>";
		}

		$this->items[] = $item;
	}
}
