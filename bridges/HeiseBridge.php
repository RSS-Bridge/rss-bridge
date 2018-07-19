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
		),
		'limit' => array(
			'name' => 'Limit',
			'type' => 'number',
			'required' => false,
			'title' => 'Specify number of full articles to return',
			'defaultValue' => 5
		)
	));
	const LIMIT = 5;

	public function collectData() {
		$this->collectExpandableDatas($this->getInput('category')); // or returnServerError('Error while downloading the website content');
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);

		$limit = $this->getInput('limit') ?: static::LIMIT;

		if(count($this->items) >= $limit) {
			return $item;
		}

		//remove ads
		foreach ($article->find('section.widget-werbung') as &$ad) {
			$ad = '';
		}

		$article = getSimpleHTMLDOMCached($item['uri']) or returnServerError('Could not open article: ' . $item['uri']);

		switch ($article->find('body', 0)->class) {
			case 'ct':
				$article = $article->find('div.ct__main__content', 0);
				$author = $article->find('li.article_page_info_author', 0)->find('a', 0);
				break;
			case 'developer':
			case 'ho':
				$author = $article->find('span.ISI_IGNORE', 0);
				$article = $article->find('div.article-content', 0);
				break;
		}

		$item['author'] = $author;
		$item['content'] = strip_tags($article, '<embetty-tweet><iframe><span><p><a><br><h1><h2><h3><img><table><tbody><tr><td><strong>');

		return $item;
	}
}
