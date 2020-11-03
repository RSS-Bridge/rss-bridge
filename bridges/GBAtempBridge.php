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

	private function cleanupPostContent($content, $site_url){
		$content = str_replace(':arrow:', '&#x27a4;', $content);
		$content = str_replace('href="attachments/', 'href="' . $site_url . 'attachments/', $content);
		$content = stripWithDelimiters($content, '<script', '</script>');
		return $content;
	}

	private function findItemDate($item){
		$time = 0;
		$dateField = $item->find('abbr.DateTime', 0);
		if (is_object($dateField)) {
			$time = intval(
				extractFromDelimiters(
					$dateField->outertext,
					'data-time="',
					'"'
				)
			);
		} else {
			$dateField = $item->find('span.DateTime', 0);
			$time = DateTime::createFromFormat(
				'M j, Y \a\t g:i A',
				extractFromDelimiters(
					$dateField->outertext,
					'title="',
					'"'
				)
			)->getTimestamp();
		}
		return $time;
	}

	private function fetchPostContent($uri, $site_url){
		$html = getSimpleHTMLDOMCached($uri);
		if(!$html) {
			return 'Could not request GBAtemp: ' . $uri;
		}

		$content = $html->find('div.messageContent, blockquote.baseHtml', 0)->innertext;
		return $this->cleanupPostContent($content, $site_url);
	}

	public function collectData(){

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request GBAtemp.');

		switch($this->getInput('type')) {
		case 'N':
			foreach($html->find('li[class=news_item full]') as $newsItem) {
				$url = self::URI . $newsItem->find('a', 0)->href;
				$img = $this->getURI() . $newsItem->find('img', 0)->src . '#.image';
				$time = $this->findItemDate($newsItem);
				$author = $newsItem->find('a.username', 0)->plaintext;
				$title = $newsItem->find('a', 1)->plaintext;
				$content = $this->fetchPostContent($url, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
				unset($newsItem); // Some items are heavy, freeing the item proactively helps saving memory
			}
			break;
		case 'R':
			foreach($html->find('li.portal_review') as $reviewItem) {
				$url = self::URI . $reviewItem->find('a', 0)->href;
				$img = $this->getURI() . extractFromDelimiters($reviewItem->find('a', 0)->style, 'image:url(', ')');
				$title = $reviewItem->find('span.review_title', 0)->plaintext;
				$content = getSimpleHTMLDOM($url)
					or returnServerError('Could not request GBAtemp: ' . $uri);
				$author = $content->find('a.username', 0)->plaintext;
				$time = $this->findItemDate($content);
				$intro = '<p><b>' . ($content->find('div#review_intro', 0)->plaintext) . '</b></p>';
				$review = $content->find('div#review_main', 0)->innertext;
				$subheader = '<p><b>' . $content->find('div.review_subheader', 0)->plaintext . '</b></p>';
				$procons = $content->find('table.review_procons', 0)->outertext;
				$scores = $content->find('table.reviewscores', 0)->outertext;
				$content = $this->cleanupPostContent($intro . $review . $subheader . $procons . $scores, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
				unset($reviewItem); // Free up memory
			}
			break;
		case 'T':
			foreach($html->find('li.portal-tutorial') as $tutorialItem) {
				$url = self::URI . $tutorialItem->find('a', 1)->href;
				$title = $tutorialItem->find('a', 1)->plaintext;
				$time = $this->findItemDate($tutorialItem);
				$author = $tutorialItem->find('a.username', 0)->plaintext;
				$content = $this->fetchPostContent($url, self::URI);
				$this->items[] = $this->buildItem($url, $title, $author, $time, null, $content);
				unset($tutorialItem); // Free up memory
			}
			break;
		case 'F':
			foreach($html->find('li.rc_item') as $postItem) {
				$url = self::URI . $postItem->find('a', 1)->href;
				$title = $postItem->find('a', 1)->plaintext;
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
