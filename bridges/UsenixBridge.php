<?php
declare(strict_types=1);

final class UsenixBridge extends BridgeAbstract
{
	const NAME = 'USENIX';
	const URI = 'https://www.usenix.org/publications';
	const DESCRIPTION = 'Digital publications from USENIX (usenix.org)';
	const MAINTAINER = 'dvikan';
	const PARAMETERS = [
		'USENIX ;login:' => [
		],
	];

	public function collectData()
	{
		if ($this->queriedContext === 'USENIX ;login:') {
			$this->collectLoginOnlineItems();
			return;
		}
		throw new Exception('Illegal context');
	}

	private function collectLoginOnlineItems(): void
	{
		$url = 'https://www.usenix.org/publications/loginonline';
		$dom = getSimpleHTMLDOMCached($url);
		$items = $dom->find('div.view-content > div');

		foreach ($items as $item) {
			$title = $item->find('.views-field-title > span', 0);
			$relativeUrl = $item->find('.views-field-nothing-1 > span > a', 0);
			// June 2, 2022
			$createdAt = $item->find('div.views-field-field-lv2-publication-date > div > span', 0);

			$this->items[] = [
				'title' => $title->innertext,
				'uri' => sprintf('https://www.usenix.org%s', $relativeUrl->href),
				'timestamp' => $createdAt->innertext,
				'content' => $item->find('.views-field-field-lv2-article-teaser > div', 0)->innertext,
			];
		}
	}
}
