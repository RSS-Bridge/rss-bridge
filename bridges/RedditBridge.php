<?php
class RedditBridge extends FeedExpander {

	const MAINTAINER = 'leomaradan';
	const NAME = 'Reddit Bridge';
	const URI = 'https://www.reddit.com/';
	const DESCRIPTION = 'Reddit bridge for Feedly';

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

		$r = $this->getInput('r');
		$rs = $this->getInput('rs');

		$subreddits = [];

		if($r !== null) {
			$subreddits[] = $r;
		}

		if($rs !== null) {
			$split = explode(',', $rs);
			$subreddits = array_merge($split, $subreddits);
		}

		foreach ($subreddits as $subreddit) {
			$name = trim($subreddit);
			$url = "https://www.reddit.com/r/$name/.rss";

			$content = getContents($url) or returnServerError('Could not request ' . $url);
			$rssContent = simplexml_load_string(trim($content));

			Debug::log('Calling function "collect_ATOM_1_0_data"');
			$this->collect_ATOM_1_0_data($rssContent, -1);
		}
	}

	protected function parseItem($newsItem) {
		return $this->parseATOMItem($newsItem);
	}
}
