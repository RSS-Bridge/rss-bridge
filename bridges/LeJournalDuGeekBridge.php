<?php
class LeJournalDuGeekBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "polopollo";
		$this->name = "journaldugeek.com (FR)";
		$this->uri = "http://www.journaldugeek.com/";
		$this->description = "Returns the 5 newest posts from LeJournalDuGeek (full text).";
	}

	private function LeJournalDuGeekStripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	private function LeJournalDuGeekExtractContent($url) {
		$articleHTMLContent = $this->getSimpleHMLDOM($url);
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

	public function collectData(array $param){
		$rssFeed = $this->getSimpleHTMLDOM('http://www.journaldugeek.com/rss') or $this->returnServerError('Could not request http://www.journaldugeek.com/rss');
		$limit = 0;

		foreach($rssFeed->find('item') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = $this->LeJournalDuGeekStripCDATA($element->find('title', 0)->innertext);
				$item->uri = $this->LeJournalDuGeekStripCDATA($element->find('guid', 0)->plaintext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->LeJournalDuGeekExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 1800; // 30min
	}
}
