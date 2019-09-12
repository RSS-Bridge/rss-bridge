<?php
class NFLRUSBridge extends BridgeAbstract {

	const NAME = 'NFLRUS';
	const URI = 'http://nflrus.ru/';
	const DESCRIPTION = 'Returns the recent articles published on nflrus.ru';
	const MAINTAINER = 'Maxim Shpak';

	private function getEnglishMonth($month) {
		$months = array(
			'Января' => 'January',
			'Февраля' => 'February',
			'Марта' => 'March',
			'Апреля' => 'April',
			'Мая' => 'May',
			'Июня' => 'June',
			'Июля' => 'July',
			'Августа' => 'August',
			'Сентября' => 'September',
			'Октября' => 'October',
			'Ноября' => 'November',
			'Декабря' => 'December',
		);

		if (isset($months[$month])) {
			return $months[$month];
		}
		return false;
	}

	private function extractArticleTimestamp($article) {
		$time = $article->find('time', 0);
		if($time) {
			$timestring = trim($time->plaintext);
			$parts = explode('  ', $timestring);
			$month = $this->getEnglishMonth($parts[1]);
			if ($month) {
				$timestring = $parts[0] . ' ' . $month . ' ' . $parts[2];
				return strtotime($timestring);
			}
		}
		return 0;
	}

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Unable to get any articles from NFLRUS');
		$html = defaultLinkTo($html, self::URI);

		foreach($html->find('article') as $article) {
			$item = array();
			$item['uri'] = $article->find('.b-article__title a', 0)->href;
			$item['title'] = $article->find('.b-article__title a', 0)->plaintext;
			$item['author'] = $article->find('.link-author', 0)->plaintext;
			$item['timestamp'] = $this->extractArticleTimestamp($article);
			$item['content'] = $article->find('div', 0)->innertext;
			$this->items[] = $item;
		}
	}
}
