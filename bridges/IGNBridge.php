<?php
class IGNBridge extends FeedExpander {

	const MAINTAINER = 'IceWreck';
	const NAME = 'IGN Bridge';
	const URI = 'https://www.ign.com/';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'RSS Feed For IGN';

	public function collectData(){
		$this->collectExpandableDatas('http://feeds.ign.com/ign/all', 15);
	}

	// IGNs feed is both hidden and incomplete. This bridge tries to fix this.

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);

		// $articlePage gets the entire page's contents
		$articlePage = getSimpleHTMLDOM($newsItem->link);

		/*
		* NOTE: Though articles and wiki/howtos have seperate styles of pages, there is no mechanism
		* for handling them seperately as it just ignores the DOM querys which it does not find.
		* (and their scraping)
		*/

		// For Articles
		$article = $articlePage->find('section.article-page', 0);
		// add in verdicts in articles, reviews etc
		foreach($articlePage->find('div.article-section') as $element) {
			$article = $article . $element;
		}

		// For Wikis and HowTos
		$uselessWikiElements = array(
			'.wiki-page-tools',
			'.feedback-container',
			'.paging-container'
		);
		foreach($articlePage->find('.wiki-page') as $wikiContents) {
			$copy = clone $wikiContents;
			// Remove useless elements present in IGN wiki/howtos
			foreach($uselessWikiElements as $uslElement) {
				$toRemove = $wikiContents->find($uslElement, 0);
				$copy = str_replace($toRemove, '', $copy);
			}
			$article = $article . $copy;
		}

		// Add content to feed
		$item['content'] = $article;
		return $item;
	}
}
