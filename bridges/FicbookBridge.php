<?php
class FicbookBridge extends BridgeAbstract {

	const NAME = 'Ficbook Bridge';
	const URI = 'https://ficbook.net/';
	const DESCRIPTION = 'No description provided';
	const MAINTAINER = 'logmanoriginal';

	const PARAMETERS = array(
		'Site News' => array(),
		'Fiction Updates' => array(
			'fiction_id' => array(
				'name' => 'Fanfiction ID',
				'type' => 'text',
				'pattern' => '[0-9]+',
				'required' => true,
				'title' => 'Insert fanfiction ID',
				'exampleValue' => '5783919',
			),
			'include_contents' => array(
				'name' => 'Include contents',
				'type' => 'checkbox',
				'title' => 'Activate to include contents in the feed',
			),
		),
		'Fiction Comments' => array(
			'fiction_id' => array(
				'name' => 'Fanfiction ID',
				'type' => 'text',
				'pattern' => '[0-9]+',
				'required' => true,
				'title' => 'Insert fanfiction ID',
				'exampleValue' => '5783919',
			),
		),
	);

	public function getURI() {
		switch($this->queriedContext) {
			case 'Site News': {
				// For some reason this is not HTTPS
				return 'http://ficbook.net/sitenews';
			}
			case 'Fiction Updates': {
				return self::URI
				. 'readfic/'
				. urlencode($this->getInput('fiction_id'));
			}
			case 'Fiction Comments': {
				return self::URI
				. 'readfic/'
				. urlencode($this->getInput('fiction_id'))
				. '/comments#content';
			}
			default: return parent::getURI();
		}
	}

	public function collectData() {

		$header = array('Accept-Language: en-US');

		$html = getSimpleHTMLDOM($this->getURI(), $header)
			or returnServerError('Could not request ' . $this->getURI());

		$html = defaultLinkTo($html, self::URI);

		switch($this->queriedContext) {
			case 'Site News': return $this->collectSiteNews($html);
			case 'Fiction Updates': return $this->collectUpdatesData($html);
			case 'Fiction Comments': return $this->collectCommentsData($html);
		}

	}

	private function collectSiteNews($html) {
		foreach($html->find('.news_view') as $news) {
			$this->items[] = array(
				'title' => $news->find('h1.title', 0)->plaintext,
				'timestamp' => strtotime($this->fixDate($news->find('span[title]', 0)->title)),
				'content' => $news->find('.news_text', 0),
			);
		}
	}

	private function collectCommentsData($html) {
		foreach($html->find('article.post') as $article) {
			$this->items[] = array(
				'uri' => $article->find('.comment_link_to_fic > a', 0)->href,
				'title' => $article->find('.comment_author', 0)->plaintext,
				'author' => $article->find('.comment_author', 0)->plaintext,
				'timestamp' => strtotime($this->fixDate($article->find('time[datetime]', 0)->datetime)),
				'content' => $article->find('.comment_message', 0),
				'enclosures' => array($article->find('img', 0)->src),
			);
		}
	}

	private function collectUpdatesData($html) {
		foreach($html->find('ul.table-of-contents > li') as $chapter) {
			$item = array(
				'uri' => $chapter->find('a', 0)->href,
				'title' => $chapter->find('a', 0)->plaintext,
				'timestamp' => strtotime($this->fixDate($chapter->find('span[title]', 0)->title)),
			);

			if($this->getInput('include_contents')) {
				$content = getSimpleHTMLDOMCached($item['uri']);
				$item['content'] = $content->find('#content', 0);
			}

			$this->items[] = $item;

			// Sort by time, descending
			usort($this->items, function($a, $b){ return $b['timestamp'] - $a['timestamp']; });
		}
	}

	private function fixDate($date) {

		// FIXME: This list was generated using Google tranlator. Someone who
		// actually knows russian should check this list! Please keep in mind
		// that month names must match exactly the names returned by Ficbook.
		$ru_month = array(
			'января',
			'февраля',
			'марта',
			'апреля',
			'мая',
			'июня',
			'июля',
			'августа',
			'Сентября',
			'октября',
			'Ноября',
			'Декабря',
		);

		$en_month = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		);

		$fixed_date = str_replace($ru_month, $en_month, $date);

		if($fixed_date === $date) {
			Debug::log('Unable to fix date: ' . $date);
			return null;
		}

		return $fixed_date;

	}
}
