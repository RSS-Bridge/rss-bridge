<?php
class SupInfoBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'SupInfoBridge';
	const URI = 'https://www.supinfo.com';
	const DESCRIPTION = 'Returns the newest articles.';

	const PARAMETERS = array(array(
		'tag' => array(
			'name' => 'Category (not mandatory)',
			'type' => 'text',
		)
	));

	public function collectData() {

		if(empty($this->getInput('tag'))) {
			$html = getSimpleHTMLDOM(self::URI . '/articles/')
				or returnServerError('Unable to fetch articles !');
		} else {
			$html = getSimpleHTMLDOM(self::URI . '/articles/tag/' . $this->getInput('tag'))
				or returnServerError('Unable to fetch articles !');
		}
		$content = $html->find('#latest', 0)->find('ul[class=courseContent]', 0);

		for($i = 0; $i < 5; $i++) {

			$this->items[] = $this->fetchArticle($content->find('h4', $i)->find('a', 0)->href);

		}
	}

	private function fetchArticle($link) {

		$articleHTML = getSimpleHTMLDOM(self::URI . $link)
			or returnServerError('Unable to fetch article !');

		$article = $articleHTML->find('div[id=courseDocZero]', 0);
		$item = array();
		$item['author'] = $article->find('#courseMetas', 0)->find('a', 0)->plaintext;
		$item['id'] = $link;
		$item['uri'] = self::URI . $link;
		$item['title'] = $article->find('h1', 0)->plaintext;
		$date = explode(' ', $article->find('#courseMetas', 0)->find('span', 1)->plaintext);
		$item['timestamp'] = DateTime::createFromFormat('d/m/Y H:i:s', $date[2] . ' ' . $date[4])->getTimestamp();

		$article->find('div[id=courseHeader]', 0)->innertext = '';
		$article->find('div[id=author-infos]', 0)->innertext = '';
		$article->find('div[id=cartouche-tete]', 0)->innertext = '';
		$item['content'] = $article;

		return $item;

	}

}
