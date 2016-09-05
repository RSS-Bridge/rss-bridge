<?php
class LeJournalDuGeekBridge extends FeedExpander {

	const MAINTAINER = "polopollo";
	const NAME = "journaldugeek.com (FR)";
	const URI = "http://www.journaldugeek.com/";
	const DESCRIPTION = "Returns the 5 newest posts from LeJournalDuGeek (full text).";

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'rss');
	}

	protected function parseItem($newsItem){
		$item = $this->parseRSS_2_0_Item($newsItem);
		$item['content'] = $this->LeJournalDuGeekExtractContent($item['uri']);
		return $item;
	}

	private function LeJournalDuGeekExtractContent($url) {
		$articleHTMLContent = $this->get_cached($url);
		$text = $articleHTMLContent->find('div.post-content', 0)->innertext;

		foreach($articleHTMLContent->find('a.more') as $element) {
			if ($element->innertext == "Source") {
				$text = $text . '<p><a href="' . $element->href . '">Source : ' . $element->href . '</a></p>';
				break;
			}
		}

		foreach($articleHTMLContent->find('iframe') as $element) {
			if (preg_match("/youtube/i", $element->src)) {
				$text = $text . '// An IFRAME to Youtube was included in the article: <a href="' . $element->src . '">' . $element->src . '</a><br>';
			}
		}

		$text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);
		$text = strip_tags($text, '<p><b><a><blockquote><img><em><br/><br><ul><li>');
		return $text;
	}

	public function getCacheDuration(){
		return 1800; // 30min
	}
}
