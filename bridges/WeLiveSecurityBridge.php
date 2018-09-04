<?php
class WeLiveSecurityBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'We Live Security';
	const URI = 'http://www.welivesecurity.com/';
	const DESCRIPTION = 'Returns the newest articles.';

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$article_html = getSimpleHTMLDOMCached($item['uri']);
		if(!$article_html) {
			$item['content'] .= '<p>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</p>';
			return $item;
		}

		$article_content = $article_html->find('div.wlistingsingletext', 0)->innertext;
		$article_content = '<p><b>'
		. $item['content']
		. '</b></p>'
		. trim($article_content);

		$item['content'] = $article_content;
		$article_content = stripWithDelimiters($article_content, '<script', '</script>');

		return $item;
	}

	public function collectData(){
		$feed = static::URI . 'feed/';
		$this->collectExpandableDatas($feed);
	}
}
