<?php //Bridge based on ExplosmBridge from bockiii
class FeedArticleExpanderBridge extends FeedExpander {

	const MAINTAINER = 'dhuschde';
	const NAME = 'Article Expander';
	const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
	const CACHE_TIMEOUT = 4800; //2hours
	const DESCRIPTION = 'You have a RSS Feed, which only gives out a small summary? <br> Then try this Bridge';
	const PARAMETERS = array(
		'' => array(
			'feed' => array(
				'name' => 'Feed URL',
				'required' => true
		),
			'ident' => array(
				'name' => 'Identifier of Content',
				'title' => 'Please give an CSS Class to identifie the Content with.',
				'required' => true
		),
			'img-ident' => array(
				'name' => 'Identifier of Image',
				'title' => 'Sometimes, the Thumbnail is seperated from the Content<br> If that is the Case, use this.<br> Please use an CSS Class again.',
		),
			'img-src' => array(
				'name' => 'Image source Attribute',
				'title' => 'The Attribute src often doesnt work, but data-src, which isnt allways there, does. So please give that Attribute.',
				'defaultValue' => 'src'
		)
	));

	public function collectData(){
		$this->collectExpandableDatas($this->getInput('feed'));
	}

	protected function parseItem($feedItem){
		$item = parent::parseItem($feedItem);
		$articlepage = getSimpleHTMLDOM($item['uri']);
		$article = $articlepage->find($this->getInput('ident'), 0);
		$image = $articlepage->find($this->getInput('img-ident'), 0)->getAttribute($this->getInput('img-src'));
		$item['content'] = '<img src="' . $image . '" />' . $article;
		return $item;
	}
}
