<?php
class BinanceBridge extends BridgeAbstract {
	const NAME = 'Binance';
	const URI = 'https://www.binance.com';
	const DESCRIPTION = 'Subscribe to the Binance blog or the Binance Zendesk announcements.';
	const MAINTAINER = 'thefranke';
	const CACHE_TIMEOUT = 3600; // 1h

	const PARAMETERS = array( array(
		'category' => array(
			'name' => 'category',
			'type' => 'list',
			'exampleValue' => 'Blog',
			'title' => 'Select a category',
			'values' => array(
				'Blog' => 'Blog',
				'Announcements' => 'Announcements'
			)
		)
	));

	public function getIcon() {
		return 'https://bin.bnbstatic.com/static/images/common/favicon.ico';
	}

	public function getName() {
		return self::NAME . ' ' . $this->getInput('category');
	}

	public function getURI() {
		if ($this->getInput('category') == 'Blog')
			return self::URI . '/en/blog';
		else
			return 'https://binance.zendesk.com/hc/en-us/categories/115000056351-Announcements';
	}

	protected function collectBlogData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not fetch Binance blog data.');

		$appData = $html->find('script[id="__APP_DATA"]');
		$appDataJson = json_decode($appData[0]->innertext);

		foreach($appDataJson->pageData->redux->blogList->blogList as $element) {

			$date = $element->postTime;
			$abstract = $element->brief;
			$uri = self::URI . '/' . $element->lang . '/blog/' . $element->idStr;
			$title = $element->title;
			$content = $element->content;

			$item = array();
			$item['title'] = $title;
			$item['uri'] = $uri;
			$item['timestamp'] = substr($date, 0, -3);
			$item['author'] = 'Binance';
			$item['content'] = $content;

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}

	protected function collectAnnouncementData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not fetch Zendesk announcement data.');

		foreach($html->find('a.article-list-link') as $a) {
			$title = $a->innertext;
			$uri = 'https://binance.zendesk.com' . $a->href;

			$full = getSimpleHTMLDOMCached($uri);
			$content = $full->find('div.article-body', 0);
			$date = $full->find('time', 0)->getAttribute('datetime');

			$item = array();

			$item['title'] = $title;
			$item['uri'] = $uri;
			$item['timestamp'] = strtotime($date);
			$item['author'] = 'Binance';
			$item['content'] = $content;

			$this->items[] = $item;

			if (count($this->items) >= 10)
				break;
		}
	}

	public function collectData() {
		if ($this->getInput('category') == 'Blog')
			$this->collectBlogData();
		else
			$this->collectAnnouncementData();
	}
}
