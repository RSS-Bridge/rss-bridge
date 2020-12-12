<?php
class RaceDepartmentBridge extends FeedExpander {
	const NAME = 'RaceDepartment News';
	const URI = 'https://racedepartment.com/';
	const DESCRIPTION = 'Get the latest (sim)racing news from RaceDepartment.';
	const MAINTAINER = 't0stiman';

	public function collectData() {
		$this->collectExpandableDatas('https://www.racedepartment.com/news/archive.rss', 10);
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);

		//fetch page
		$articlePage = getSimpleHTMLDOMCached($feedItem->link)
			or returnServerError('Could not retrieve ' . $feedItem->link);
		//extract article
		$item['content'] = $articlePage->find('div.thfeature_firstPost', 0);

		//convert iframes to links. meant for embedded videos.
		foreach($item['content']->find('iframe') as $found) {

			$iframeUrl = $found->getAttribute('src');

			if ($iframeUrl) {
				$found->outertext = '<a href="' . $iframeUrl . '">' . $iframeUrl . '</a>';
			}
		}

		//get rid of some elements we don't need
		$to_remove_selectors = array(
			'div.p-title',		//title
			'ul.listInline',	//Thread starter, Start date
			'div.rd_news_article_share_buttons',
			'div.thfeature_firstPost-author',
			'div.reactionsBar',
			'footer',
			'div.message-lastEdit',
			'section.message-attachments'
		);

		foreach($to_remove_selectors as $selector) {
			foreach($item['content']->find($selector) as $found) {
				$found->outertext = '';
			}
		}

		//category
		$forumPath = $articlePage->find('div.breadcrumb', 0);
		$pathElements = $forumPath->find('span');
		$item['categories'] = array(end($pathElements)->innertext);

		return $item;
	}
}
