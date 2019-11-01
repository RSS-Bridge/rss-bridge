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
			$url = "https://www.reddit.com/r/$name/.rss";

			// If in CLI mode with no root certificates defined, skip the url verification
			if(php_sapi_name() !== 'cli' || !empty(ini_get('curl.cainfo'))) {
				// We must test if the subreddit exists before gathering the content
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_USERAGENT, ini_get('user_agent'));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_exec($ch);
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if($httpcode !== 403) {
					$this->collectExpandableDatas($url);
				}
			} else {
				$this->collectExpandableDatas($url);
			}
		}
	}
}
