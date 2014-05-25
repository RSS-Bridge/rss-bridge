<?php
/**
* RssBridgeTwitter 
* Based on https://github.com/mitsukarenai/twitterbridge-noapi
* 2014-05-25
*
* @name Twitter Bridge Extended
* @homepage https://twitter.com/
* @description (same as Twitter Bridge, but with avatar, replies and RTs)
* @maintainer mitsukarenai
* @use1(q="keyword or hashtag")
* @use2(u="username")
*/
class TwitterBridgeExtended extends BridgeAbstract{

	public function collectData(array $param){
		$html = '';
		if (isset($param['q'])) {   /* keyword search mode */
			$html = file_get_html('http://twitter.com/search/realtime?q='.urlencode($param['q']).'+include:retweets&src=typd') or $this->returnError('No results for this query.', 404);
		}
		elseif (isset($param['u'])) {   /* user timeline mode */
			$html = file_get_html('http://twitter.com/'.urlencode($param['u']).'/with_replies') or $this->returnError('Requested username can\'t be found.', 404);
		}
		else {
			$this->returnError('You must specify a keyword (?q=...) or a Twitter username (?u=...).', 400);
		}

		foreach($html->find('div.js-stream-tweet') as $tweet) {
			$item = new \Item();
			// extract username and sanitize
			$item->username = $tweet->getAttribute('data-screen-name');
			// extract fullname (pseudonym)
			$item->fullname = $tweet->getAttribute('data-name'); 
			// get avatar link
			$item->avatar = $tweet->find('img', 0)->src;	
			// get TweetID
			$item->id = $tweet->getAttribute('data-tweet-id');
			// get tweet link	
			$item->uri = 'https://twitter.com'.$tweet->find('a.js-permalink', 0)->getAttribute('href');	
			// extract tweet timestamp
			$item->timestamp = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// extract plaintext	
			$item->content_simple = str_replace('href="/', 'href="https://twitter.com/', html_entity_decode(strip_tags($tweet->find('p.js-tweet-text', 0)->innertext, '<a>'))); 
	
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

			// get tweet text
			$item->content = '<a href="https://twitter.com/'.$item->username.'"><img align="top" alt="avatar" src="'.$item->avatar.'" />'.$item->username.'</a> '.$item->fullname.'<br/><blockquote>'.str_replace('href="/', 'href="https://twitter.com/', $tweet->find('p.js-tweet-text', 0)->innertext).'</blockquote>';
			// generate the title
			$item->title = $item->fullname . ' (@'. $item->username . ') | ' . $item->content_simple;
			// put out
			$this->items[] = $item;
		}
	}

	public function getName(){
		return 'Twitter Bridge Extended';
	}

	public function getURI(){
		return 'http://twitter.com';
	}

	public function getCacheDuration(){
		return 300; // 5 minutes
	}
}
