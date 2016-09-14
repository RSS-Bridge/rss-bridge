<?php
class SiliconBridge extends FeedExpander {

	const MAINTAINER = "ORelio";
	const NAME = 'Silicon Bridge';
	const URI = 'http://www.silicon.fr/';
	const DESCRIPTION = "Returns the newest articles.";

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$article_html = $this->getSimpleHTMLDOMCached($item['uri']);
		if(!$article_html){
			$item['content'] .= '<p>Could not request Silicon: '.$item['uri'].'</p>';
			return $item;
		}

		$article_content = '<p><b>'.$article_html->find('div.entry-excerpt', 0)->plaintext.'</b></p>'
			.$article_html->find('div.entry-content', 0)->innertext;

		//Remove useless scripts left in the page
		while (strpos($article_content, '<script') !== false) {
			$script_section = substr($article_content, strpos($article_content, '<script'));
			$script_section = substr($script_section, 0, strpos($script_section, '</script>') + 9);
			$article_content = str_replace($script_section, '', $article_content);
		}

		$item['content'] = $article_content;

		return $item;
	}

	public function collectData(){
		$feedUrl = self::URI.'feed';
		$this->collectExpandableDatas($feedUrl);
	}

	public function getCacheDuration() {
		return 1800; // 30 minutes
	}
}
