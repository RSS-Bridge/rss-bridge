<?php
class BinanceBridge extends BridgeAbstract {
	const NAME = 'Binance';
	const URI = 'https://www.binance.com';
	const DESCRIPTION = 'Subscribe to the Binance blog.';
	const MAINTAINER = 'thefranke';
	const CACHE_TIMEOUT = 3600; // 1h

	public function getIcon() {
		return 'https://bin.bnbstatic.com/static/images/common/favicon.ico';
	}

	public function getName() {
		return self::NAME . ' blog';
	}

	public function getURI() {
		return self::URI . '/en/blog';
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

	public function collectData() {
		$this->collectBlogData();
	}
}
