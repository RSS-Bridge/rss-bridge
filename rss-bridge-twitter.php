<?php
require_once('rss-bridge-lib.php');

/**
 * RssBridgeTwitter 
 * Based on https://github.com/mitsukarenai/twitterbridge-noapi
 */
class RssBridgeTwitter extends RssBridgeAbstractClass
{
    protected $bridgeName = 'Twitter Bridge';
    protected $bridgeURI = 'http://twitter.com';
    protected $bridgeDescription = 'Returns user timelines or keyword search from http://twitter.com without using their API.';
    protected $cacheDuration = 5; // 5 minutes
    protected function collectData($request) {
        $html = '';
        if (isset($request['q'])) {   /* keyword search mode */
            $html = file_get_html('http://twitter.com/search/realtime?q='.urlencode($request['q']).'+include:retweets&src=typd') or $this->returnError('404 Not Found', 'ERROR: no results for this query.');
        } elseif (isset($request['u'])) {   /* user timeline mode */
            $html = file_get_html('http://twitter.com/'.urlencode($request['u'])) or $this->returnError('404 Not Found', 'ERROR: requested username can\'t be found.');
        } else {
            $this->returnError('400 Bad Request', 'ERROR: You must specify a keyword (?q=...) or a Twitter username (?u=...).');
        }
        $this->items = Array();
        foreach($html->find('div.tweet') as $tweet) {
            $item['username'] = trim(substr($tweet->find('span.username', 0)->plaintext, 1));	// extract username and sanitize
            $item['fullname'] = $tweet->getAttribute('data-name'); // extract fullname (pseudonym)
            $item['avatar']	= $tweet->find('img', 0)->src;	// get avatar link
            $item['id']	= $tweet->getAttribute('data-tweet-id');	// get TweetID
            $item['uri'] = 'https://twitter.com'.$tweet->find('a.details', 0)->getAttribute('href');	// get tweet link
            $item['timestamp']	= $tweet->find('span._timestamp', 0)->getAttribute('data-time');	// extract tweet timestamp
            $item['content'] = str_replace('href="/', 'href="https://twitter.com/', strip_tags($tweet->find('p.tweet-text', 0)->innertext, '<a>'));	// extract tweet text
            $item['title'] = $item['fullname'] . ' (@'.$item['username'] . ') | ' . $item['content'];
            $this->items[] = $item;
        }
    }
} 

$bridge = new RssBridgeTwitter();
$bridge->process();
?>