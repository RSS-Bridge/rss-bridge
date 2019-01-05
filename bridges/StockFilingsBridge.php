<?php

class StockFilingsBridge extends BridgeAbstract {
	const MAINTAINER = 'captn3m0';
	const NAME = 'SEC Stock filings';
	const URI = 'https://www.sec.gov/edgar/searchedgar/companysearch.html';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Tracks SEC Filings for a single company';
	const SEARCH_URL = 'https://www.sec.gov/cgi-bin/browse-edgar?owner=exclude&action=getcompany&CIK=';

	const PARAMETERS = array(
		array(
		'ticker' => array(
			'name' 			=> 'cik',
			'required' 		=> true,
			'exampleValue' 	=> 'AMD',
			// https://stackoverflow.com/a/12827734
			'pattern'		=> '[A-Za-z0-9]+',
		),
	));

	protected $title;

	/**
	 * Generates search URL
	 */
	private function getSearchUrl() {
		return self::SEARCH_URL . $this->getInput('ticker');
	}

	/**
	 * Returns the Company Name
	 */
	private function getTitle($html) {
		$titleTag = $html->find('span.companyName', 0);

		if (!$titleTag) {
			return "No Such Company";
		} else {
			// Remove <acronym> and <a> tags
			foreach($titleTag->children as $child) {
				$child->outertext = "";
			}
			return substr($titleTag->innertext, 0, -4);
		}
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

	/**
	 * Return \simple_html_dom object
	 * for the entire html of the product page
	 */
	private function getHtml() {
		$uri = $this->getSearchUrl();

		return getSimpleHTMLDOM($uri) ?: returnServerError('Could not request SEC.');
	}

	/**
	 * Scrape method for Amazon product page
	 * @return [type] [description]
	 */
	public function collectData() {
		$html = $this->getHtml();
		$this->title = $this->getTitle($html);
		$this->

		// $data = $this->scrapePriceFromMetrics($html) ?: $this->scrapePriceGeneric($html);

		// $item = array(
		// 	'title' 	=> $this->title,
		// 	'uri' 		=> $this->getURI(),
		// 	'content' 	=> "$imageTag<br/>Price: {$data['price']} {$data['currency']}",
		// );

		// if ($data['shipping'] !== '0') {
		// 	$item['content'] .= "<br>Shipping: {$data['shipping']} {$data['currency']}</br>";
		// }

		// $this->items[] = $item;
	}
}
