<?php

class HeiseBridge extends FeedExpander {
	const MAINTAINER = 'Dreckiger-Dan';
	const NAME = 'Heise Online Bridge';
	const URI = 'https://heise.de/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns the full articles instead of only the intro';
	const PARAMETERS = array(array(
		'category' => array(
			'name' => 'Category',
			'type' => 'list',
			'required' => true,
			'values' => array(
				'Alle News' => 'https://www.heise.de/newsticker/heise-atom.xml',
				'Top-News' => 'https://www.heise.de/newsticker/heise-top-atom.xml',
				'Internet-Störungen' => 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/',
				'Alle News von heise Developer' => 'https://www.heise.de/developer/rss/news-atom.xml'
			)
		)
	));

	public function collectData() {
		//ini_set('memory_limit', '-1'); //dirty workaround for memory limitation
		$this->collectExpandableDatas($this->getInput('category')); // or returnServerError('Error while downloading the website content');
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);

		$article = getSimpleHTMLDOMCached($item['uri']) or returnServerError('Could not open article: ' . $url);
		$author = $article->find('span.ISI_IGNORE', 0);

		//remove ads
		foreach ($article->find('section.widget-werbung') as &$ad) {
			$ad = '';
		}
		$article = $article->find('div.article-content', 0);

		$item['author'] = $author;
		$item['content'] = strip_tags($article, '<span><p><a><br><h1><h2><h3><img><table><tbody><tr><td>');

		return $item;
	}
}
