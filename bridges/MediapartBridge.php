<?PHP

class MediapartBridge extends FeedExpander {
	const MAINTAINER = 'killruana';
	const NAME = 'Mediapart Bridge';
	const URI = 'https://www.mediapart.fr/';
	const PARAMETERS = array(
		array(
			'MPSESSID' => array(
				'name' => 'MPSESSID',
				'type' => 'text',
				'title' => 'Cookie session'
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

		$opt = array();
		$mpsessid = $this->getInput('MPSESSID');
		if (!empty($mpsessid)) {
			$opt[CURLOPT_COOKIE] = 'MPSESSID=' . $mpsessid;
		}

		$articlePage = getSimpleHTMLDOM($item['uri'], array(), $opt);
		$introduction = $articlePage->find('div.introduction', 0)->innertext;
		$article = $articlePage->find('div.content-article', 0)->innertext;
		$content = sanitize($introduction . $article);
		$content = defaultLinkTo($content, static::URI);
		$item['content'] = $content;

		return $item;
	}
}
