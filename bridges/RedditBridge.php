<?php
class RedditBridge extends FeedExpander {

	const MAINTAINER = 'leomaradan';
	const NAME = 'Reddit Bridge';
	const URI = 'https://www.reddit.com/';
	const DESCRIPTION = 'Reddit RSS Feed fixer';

	const PARAMETERS = array(
		'single' => array(
			'r' => array(
				'name' => 'SubReddit',
				'required' => true,
				'exampleValue' => 'selfhosted',
				'title' => 'SubReddit name'
			)
		),
		'multi' => array(
			'rs' => array(
				'name' => 'SubReddits',
				'required' => true,
				'exampleValue' => 'selfhosted, php',
				'title' => 'SubReddit names, separated by commas'
			)
		)
	);

	public function collectData(){

		switch($this->queriedContext) {
			case 'single': $subreddits[] = $this->getInput('r'); break;
			case 'multi': $subreddits = explode(',', $this->getInput('rs')); break;
	}

		foreach ($subreddits as $subreddit) {
			$name = trim($subreddit);
			$this->collectExpandableDatas("https://www.reddit.com/r/$name/.rss");
		}
	}
}
