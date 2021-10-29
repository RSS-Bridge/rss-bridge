<?php
class GBAtempBridge extends BridgeAbstract {

	const MAINTAINER = 'ORelio';
	const NAME = 'GBAtemp';
	const URI = 'https://gbatemp.net/';
	const DESCRIPTION = 'GBAtemp is a user friendly underground video game community.';

	const PARAMETERS = array( array(
		'type' => array(
			'name' => 'Type',
			'type' => 'list',
			'values' => array(
				'News' => 'N',
				'Reviews' => 'R',
				'Tutorials' => 'T',
				'Forum' => 'F'
			)
		)
	));

	private function buildItem($uri, $title, $author, $timestamp, $thumbnail, $content){
		$item = array();
		$item['uri'] = $uri;
		$item['title'] = $title;
		$item['author'] = $author;
		$item['timestamp'] = $timestamp;
		$item['content'] = $content;
		if (!empty($thumbnail)) {
			$item['enclosures'] = array($thumbnail);
		}
		return $item;
	}

	private function strEndsWith($haystack, $needle){
		// str_ends_with is not available below PHP 8
		$length = strlen($needle);
		return $length > 0 ? substr($haystack, -$length) === $needle : true;
	}

	private function decodeHtmlEntities($text) {
		$text = html_entity_decode($text);
		$convmap = array(0x0, 0x2FFFF, 0, 0xFFFF);
		return trim(mb_decode_numericentity($text, $convmap, 'UTF-8'));
	}

	private function cleanupPostContent($content, $site_url){
		$content = str_replace('src="/', 'src="' . $site_url, $content);
		$content = str_replace('href="/', 'href="' . $site_url, $content);
		$content = stripWithDelimiters($content, '<script', '</script>');
		$content = stripWithDelimiters($content, '<svg', '</svg>');
		$content = stripRecursiveHTMLSection($content, 'div', '<div class="reactionsBar');
		return $this->decodeHtmlEntities($content);
	}

	private function makeAbsoluteUrl($link) {
		if (strpos($link, '/') === 0) {
			$link = substr($link, 1);
		}
		return self::URI . $link;
	}

	private function findItemDate($item){
		$time = 0;
		$dateField = $item->find('time', 0);
		if (is_object($dateField)) {
			$time = strtotime($dateField->datetime);
		}
		return $time;
	}

	private function findItemImage($item, $selector){
		$img = extractFromDelimiters($item->find($selector, 0)->style, 'url(', ')');
		$paramPos = strpos($img, '?');
		if ($paramPos !== false) {
			$img = substr($img, 0, $paramPos);
		}
		if (!$this->strEndsWith($img, '.png') && !$this->strEndsWith($img, '.jpg')) {
			$img = $img . '#.image';
		}
		return $this->makeAbsoluteUrl($img);
	}

	private function fetchPostContent($uri, $site_url){
		$html = getSimpleHTMLDOMCached($uri);
		if(!$html) {
			return 'Could not request GBAtemp: ' . $uri;
		}

		$content = $html->find('article.message-body', 0)->innertext;
		return $this->cleanupPostContent($content, $site_url);
	}

	public function collectData(){

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request GBAtemp.');

		switch($this->getInput('type')) {
		case 'N':
			foreach($html->find('li.news_item.full') as $newsItem) {
				$url = $this->makeAbsoluteUrl($newsItem->find('a', 0)->href);
				$img = $this->findItemImage($newsItem, 'a.news_image');
				$time = $this->findItemDate($newsItem);
				$author = $newsItem->find('a.username', 0)->plaintext;
				$title = $this->decodeHtmlEntities($newsItem->find('h3.news_title', 0)->plaintext);
				$content = $this->fetchPostContent($url, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
				unset($newsItem); // Some items are heavy, freeing the item proactively helps saving memory
			}
			break;
		case 'R':
			foreach($html->find('li.portal_review') as $reviewItem) {
				$url = $this->makeAbsoluteUrl($reviewItem->find('a.review_boxart', 0)->href);
				$img = $this->findItemImage($reviewItem, 'a.review_boxart');
				$title = $this->decodeHtmlEntities($reviewItem->find('h2.review_title', 0)->plaintext);
				$content = getSimpleHTMLDOMCached($url)
					or returnServerError('Could not request GBAtemp: ' . $uri);
				$author = $content->find('span.author--name', 0)->plaintext;
				$time = $this->findItemDate($content);
				$intro = '<p><b>' . ($content->find('div#review_introduction', 0)->plaintext) . '</b></p>';
				$review = $content->find('div#review_main', 0)->innertext;
				$content = $this->cleanupPostContent($intro . $review, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
				unset($reviewItem); // Free up memory
			}
			break;
		case 'T':
			foreach($html->find('li.portal-tutorial') as $tutorialItem) {
				$url = $this->makeAbsoluteUrl($tutorialItem->find('a', 1)->href);
				$title = $this->decodeHtmlEntities($tutorialItem->find('a', 1)->plaintext);
				$time = $this->findItemDate($tutorialItem);
				$author = $tutorialItem->find('a.username', 0)->plaintext;
				$content = $this->fetchPostContent($url, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, null, $content);
				unset($tutorialItem); // Free up memory
			}
			break;
		case 'F':
			foreach($html->find('li.rc_item') as $postItem) {
				$url = $this->makeAbsoluteUrl($postItem->find('a', 1)->href);
				$title = $this->decodeHtmlEntities($postItem->find('a', 1)->plaintext);
				$time = $this->findItemDate($postItem);
				$author = $postItem->find('a.username', 0)->plaintext;
				$content = $this->fetchPostContent($url, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, null, $content);
				unset($postItem); // Free up memory
			}
			break;
		}
	}

	public function getName() {
		if(!is_null($this->getInput('type'))) {
			$type = array_search(
				$this->getInput('type'),
				self::PARAMETERS[$this->queriedContext]['type']['values']
			);
			return 'GBAtemp ' . $type . ' Bridge';
		}

		return parent::getName();
	}
}
