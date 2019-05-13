<?PHP

class MediapartBridge extends FeedExpander {
	const MAINTAINER = 'killruana';
	const NAME = 'Mediapart Bridge';
	const URI = 'https://www.mediapart.fr/';
	const PARAMETERS = array(
		array(
			'mpsessid' => array(
				'name' => 'MPSESSID',
				'type' => 'text',
				'title' => 'Value of the session cookie MPSESSID'
			)
		)
	);
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Returns the newest articles.';

	public function collectData() {
		$url = self::URI . 'articles/feed';
		$this->collectExpandableDatas($url);
	}

	protected function parseItem($newsItem) {
		$item = parent::parseItem($newsItem);
		$item['uri'] .= '?onglet=full';

		$mpsessid = $this->getInput('mpsessid');
		if (!empty($mpsessid)) {
			$opt = array();
			$opt[CURLOPT_COOKIE] = 'MPSESSID=' . $mpsessid;
			$articlePage = getSimpleHTMLDOM($item['uri'], array(), $opt);
			$content = $articlePage->find('div.content-article', 0)->innertext;
			$content = sanitize($content);
			$content = defaultLinkTo($content, static::URI);
			$item['content'] .= $content;
		}

		return $item;
	}
}
