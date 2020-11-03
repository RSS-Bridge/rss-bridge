<?php
class VieDeMerdeBridge extends BridgeAbstract {

	const MAINTAINER = 'floviolleau';
	const NAME = 'VieDeMerde Bridge';
	const URI = 'https://viedemerde.fr';
	const DESCRIPTION = 'Returns latest quotes from VieDeMerde.';
	const CACHE_TIMEOUT = 7200;

	const PARAMETERS = array(array(
			'item_limit' => array(
			'name' => 'Limit number of returned items',
			'type' => 'number',
			'defaultValue' => 20
		)
	));

	public function collectData() {
		$limit = $this->getInput('item_limit');

		if ($limit < 1) {
			$limit = 20;
		}

		$html = getSimpleHTMLDOM(self::URI, array())
			or returnServerError('Could not request VieDeMerde.');

		$quotes = $html->find('article.article-panel');
		if(sizeof($quotes) === 0) {
			return;
		}

		foreach($quotes as $quote) {
			$item = array();
			$item['uri'] = self::URI . $quote->find('.article-contents a', 0)->href;
			$titleContent = $quote->find('.article-contents a h2.classic-title', 0);

			if($titleContent) {
				$item['title'] = html_entity_decode($titleContent->plaintext, ENT_QUOTES);
			} else {
				continue;
			}

			$quote->find('.article-contents a h2.classic-title', 0)->outertext = '';
			$item['content'] = $quote->find('.article-contents a', 0)->innertext;
			$item['author'] = $quote->find('.article-topbar', 0)->innertext;
			$item['uid'] = hash('sha256', $item['title']);

			$this->items[] = $item;

			if (count($this->items) >= $limit) {
				break;
			}
		}
	}
}
