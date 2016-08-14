<?php
class TwitterBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Twitter Bridge";
		$this->uri = "https://twitter.com/";
		$this->description = "Returns tweets by keyword/hashtag or user name";
		$this->update = "2016-08-15";

		$this->parameters["global"] = 
		'[
			{
				"name" : "Hide profile pictures",
				"identifier" : "pic",
				"type" : "checkbox",
				"required" : false,
				"exampleValue" : "checked",
				"title" : "Activate to hide profile pictures in content"
			}
		]';

		$this->parameters["By keyword or hashtag"] =
		'[
			{
				"name" : "Keyword or #hashtag",
				"identifier" : "q",
				"type" : "text",
				"required" : true,
				"exampleValue" : "rss-bridge, #rss-bridge",
				"title" : "Insert a keyword or hashtag"
			}
		]';

		$this->parameters["By username"] =
		'[
			{
				"name" : "username",
				"identifier" : "u",
				"type" : "text",
				"required" : true,
				"exampleValue" : "sebsauvage",
				"title" : "Insert a user name"
			}
		]';

	}

	public function collectData(array $param){
		$html = ''; 
		if (isset($param['q'])) {   /* keyword search mode */
			$html = $this->file_get_html('https://twitter.com/search?q='.urlencode($param['q']).'&f=tweets') or $this->returnError('No results for this query.', 404);
		}
		elseif (isset($param['u'])) {   /* user timeline mode */
			$html = $this->file_get_html('https://twitter.com/'.urlencode($param['u']).'/with_replies') or $this->returnError('Requested username can\'t be found.', 404);
		}
		else {
			$this->returnError('You must specify a keyword (?q=...) or a Twitter username (?u=...).', 400);
		}

		$hidePictures = false;
		if (isset($param['pic']))
			$hidePictures = $param['pic'] === 'on';

		foreach($html->find('div.js-stream-tweet') as $tweet) {
			$item = new \Item();
			// extract username and sanitize
			$item->username = $tweet->getAttribute('data-screen-name');
			// extract fullname (pseudonym)
			$item->fullname = $tweet->getAttribute('data-name'); 
			// get author
			$item->author = $item->fullname . ' (@' . $item->username . ')';
			// get avatar link
			$item->avatar = $tweet->find('img', 0)->src;	
			// get TweetID
			$item->id = $tweet->getAttribute('data-tweet-id');
			// get tweet link	
			$item->uri = 'https://twitter.com'.$tweet->find('a.js-permalink', 0)->getAttribute('href');	
			// extract tweet timestamp
			$item->timestamp = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// generate the title
			$item->title = strip_tags($tweet->find('p.js-tweet-text', 0)->innertext); 
	
			// processing content links
			foreach($tweet->find('a') as $link) {
				if($link->hasAttribute('data-expanded-url') ) {
					$link->href = $link->getAttribute('data-expanded-url');
				}
				$link->removeAttribute('data-expanded-url');
				$link->removeAttribute('data-query-source');
				$link->removeAttribute('rel');
				$link->removeAttribute('class');
				$link->removeAttribute('target');
				$link->removeAttribute('title');
			}

			// process emojis (reduce size)
			foreach($tweet->find('img.Emoji') as $img){
				$img->style .= ' height: 1em;';
			}

			// get tweet text
			$cleanedTweet = str_replace('href="/', 'href="https://twitter.com/', $tweet->find('p.js-tweet-text', 0)->innertext);

			// Add picture to content
			$picture_html = '';
			if(!$hidePictures){
				$picture_html = <<<EOD
<a href="https://twitter.com/{$item->username}"><img style="align: top; width:75 px; border: 1px solid black;" alt="{$item->username}" src="{$item->avatar}" title="{$item->fullname}" /></a>
EOD;
			}

			// add content
			$item->content = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	{$picture_html}
</div>
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedTweet}</blockquote>
</div>
EOD;

			// put out
			$this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 300; // 5 minutes
	}
}
