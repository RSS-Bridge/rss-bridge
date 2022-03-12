<?php
class PhoronixBridge extends FeedExpander {

	const MAINTAINER = 'IceWreck';
	const NAME = 'Phoronix Bridge';
	const URI = 'https://www.phoronix.com';
	const CACHE_TIMEOUT = 3600;
	const DESCRIPTION = 'RSS feed for Linux news website Phoronix';
	const PARAMETERS = array(array(
		'n' => array(
			'name' => 'Limit',
			'type' => 'number',
			'required' => false,
			'title' => 'Maximum number of items to return',
			'defaultValue' => 10
		)
	));

	public function collectData(){
		$this->collectExpandableDatas('https://www.phoronix.com/rss.php', $this->getInput('n'));
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		// $articlePage gets the entire page's contents
		$articlePage = getSimpleHTMLDOM($newsItem->link);
		$article = $articlePage->find('.content', 0);
		$item['content'] = $article;
		return $item;
	}
}
