<?php
class OMGUbuntuBridge extends FeedExpander {
	const NAME = 'OMG! Ubuntu! News';
	const URI = 'https://omgubuntu.co.uk/';
	const DESCRIPTION = 'News about Ubuntu, Linux and open-source software.';
	const MAINTAINER = 't0stiman';

	public function collectData() {
		$this->collectExpandableDatas('http://feeds.feedburner.com/d0od');
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);

		$articlePage = getSimpleHTMLDOMCached($feedItem->link);
		$article = $articlePage->find('div.post-content', 0);

		//get rid of some elements we don't need
		$article = str_replace('<ul class="omg-socials">', '<ul style="display: none;">', $article);
		$article = str_replace('<div class="post-links"', '<div style="display: none;">', $article);
		$article = str_replace('<div class="post-links post-links--tags"', '<div style="display: none;">', $article);

		$item['content'] = $article;

		return $item;
	}
}
