<?php
class HaveIBeenPwnedBridge extends BridgeAbstract {
	const NAME = 'Have I Been Pwned (HIBP) Bridge';
	const URI = 'https://haveibeenpwned.com';
	const DESCRIPTION = 'Returns list of Pwned websites';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'order' => array(
			'name' => 'Order by',
			'type' => 'list',
			'values' => array(
				'Breach date' => 'breachDate',
				'Date added to HIBP' => 'dateAdded',
			),
			'defaultValue' => 'dateAdded',
		),
		'item_limit' => array(
			'name' => 'Limit number of returned items',
			'type' => 'number',
			'defaultValue' => 20,
		)
	));

	const CACHE_TIMEOUT = 3600;

	private $breachDateRegex = '/Breach date: ([0-9]{1,2} [A-Z-a-z]+ [0-9]{4})/';
	private $dateAddedRegex = '/Date added to HIBP: ([0-9]{1,2} [A-Z-a-z]+ [0-9]{4})/';
	private $accountsRegex = '/Compromised accounts: ([0-9,]+)/';

	private $breaches = array();

	public function collectData() {

		$html = getSimpleHTMLDOM(self::URI . '/PwnedWebsites')
			or returnServerError('Could not request: ' . self::URI . '/PwnedWebsites');

		$breaches = array();

		foreach($html->find('div.row') as $breach) {
			$item = array();

			if ($breach->class != 'row') {
				continue;
			}

			preg_match($this->breachDateRegex, $breach->find('p', 1)->plaintext, $breachDate)
				or returnServerError('Could not extract details');

			preg_match($this->dateAddedRegex, $breach->find('p', 1)->plaintext, $dateAdded)
				or returnServerError('Could not extract details');

			preg_match($this->accountsRegex, $breach->find('p', 1)->plaintext, $accounts)
				or returnServerError('Could not extract details');

			$permalink = $breach->find('p', 1)->find('a', 0)->href;

			// Remove permalink
			$breach->find('p', 1)->find('a', 0)->outertext = '';

			$item['title'] = html_entity_decode($breach->find('h3', 0)->plaintext, ENT_QUOTES)
				. ' - ' . $accounts[1] . ' breached accounts';
			$item['dateAdded'] = strtotime($dateAdded[1]);
			$item['breachDate'] = strtotime($breachDate[1]);
			$item['uri'] = self::URI . '/PwnedWebsites' . $permalink;

			$item['content'] = '<p>' . $breach->find('p', 0)->innertext . '</p>';
			$item['content'] .= '<p>' . $this->breachType($breach) . '</p>';
			$item['content'] .= '<p>' . $breach->find('p', 1)->innertext . '</p>';

			$this->breaches[] = $item;
		}

		$this->orderBreaches();
		$this->createItems();
	}

	/**
	 * Extract data breach type(s)
	 */
	private function breachType($breach) {

		$content = '';

		if ($breach->find('h3 > i', 0)) {

			foreach ($breach->find('h3 > i') as $i) {
				$content .= $i->title . '.<br>';
			}

		}

		return $content;

	}

	/**
	 * Order Breaches by date added or date breached
	 */
	private function orderBreaches() {

		$sortBy = $this->getInput('order');
		$sort = array();

		foreach ($this->breaches as $key => $item) {
			$sort[$key] = $item[$sortBy];
		}

		array_multisort($sort, SORT_DESC, $this->breaches);

	}

	/**
	 * Create items from breaches array
	 */
	private function createItems() {

		$limit = $this->getInput('item_limit');

		if ($limit < 1) {
			$limit = 20;
		}

		foreach ($this->breaches as $breach) {
			$item = array();

			$item['title'] = $breach['title'];
			$item['timestamp'] = $breach[$this->getInput('order')];
			$item['uri'] = $breach['uri'];
			$item['content'] = $breach['content'];

			$this->items[] = $item;

			if (count($this->items) >= $limit) {
				break;
			}
		}
	}
}
