<?php
class WosckerBridge extends BridgeAbstract {
	const NAME = 'Woscker Bridge';
	const URI = 'https://woscker.com/';
	const DESCRIPTION = 'Returns news of the day';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 1800; // 30 mins

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$date = $html->find('h1', 0)->plaintext;
		$timestamp = $html->find('span.dateFont', 0)->plaintext . ' ' . $html->find('span.dateFont', 1)->plaintext;

		$item = array();
		$item['title'] = $date;
		$item['content'] = $this->formatContent($html);
		$item['timestamp'] = $timestamp;

		$this->items[] = $item;
	}

	private function formatContent($html) {
		$html->find('h1', 0)->outertext = '';

		foreach ($html->find('hr') as $hr) {
			$hr->outertext = '';
		}

		foreach ($html->find('div.betweenHeadline') as $div) {
			$div->outertext = '';
		}

		foreach ($html->find('div.dividingBarrier') as $div) {
			$div->outertext = '';
		}

		foreach ($html->find('h2') as $h2) {
			$h2->outertext = '<br><strong>' . $h2->innertext . '</strong><br>';
		}

		foreach ($html->find('h3') as $h3) {
			$h3->outertext = $h3->innertext . '<br>';
		}

		return $html->find('div.fullContentPiece', 0)->innertext;
	}
}
