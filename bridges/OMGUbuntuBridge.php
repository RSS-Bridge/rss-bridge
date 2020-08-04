<?php
class OMGUbuntuBridge extends FeedExpander {
	const NAME = 'OMG! Ubuntu! News';
	const URI = 'https://omgubuntu.co.uk/';
	const DESCRIPTION = 'News about Ubuntu, Linux and open-source software.';
	const MAINTAINER = 't0stiman';

	public function collectData() {
		$this->collectExpandableDatas('http://feeds.feedburner.com/d0od', 20);
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);

		$articlePage = getSimpleHTMLDOMCached($feedItem->link);
		$item['content'] = $articlePage->find('div.post-content', 0);

		//convert iframes to links. meant for embedded videos.
		foreach($item['content']->find('iframe') as $found) {
			$pattern = '/src="(.+?)"/i';
			if(preg_match($pattern, $found->outertext, $match)) {
				$iframeUrl = $match[1];
				$found->outertext = '<a href="' . $iframeUrl . '">' . $iframeUrl . '</a>';
			}
		}

		//category
		$categoryContainer = $item['content']->find('div.post-links--tags', 0);
		foreach ($categoryContainer->find('a') as $a) {
			$category = $a->innertext;
			//remove # at start
			$item['categories'][] = preg_replace('/^ +?#/', '', $category);
		}

		//get rid of some elements we don't need
		$to_remove_selectors = array(
			'ul.omg-socials',
			'div.post-links'
		);

		foreach($to_remove_selectors as $selector) {
			foreach($item['content']->find($selector) as $found) {
				$found->outertext = '';
			}
		}

		return $item;
	}
}
