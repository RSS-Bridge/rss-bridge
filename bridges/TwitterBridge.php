<?php
class TwitterBridge extends BridgeAbstract {
	const NAME = 'Twitter Bridge';
	const URI = 'https://twitter.com/';
	const API_URI = 'https://api.twitter.com';
	const GUEST_TOKEN_USES = 100;
	const GUEST_TOKEN_EXPIRY = 10800; // 3hrs
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'returns tweets';
	const MAINTAINER = 'arnd-s';
	const PARAMETERS = array(
		'global' => array(
			'nopic' => array(
				'name' => 'Hide profile pictures',
				'type' => 'checkbox',
				'title' => 'Activate to hide profile pictures in content'
			),
			'noimg' => array(
				'name' => 'Hide images in tweets',
				'type' => 'checkbox',
				'title' => 'Activate to hide images in tweets'
			),
			'noimgscaling' => array(
				'name' => 'Disable image scaling',
				'type' => 'checkbox',
				'title' => 'Activate to disable image scaling in tweets (keeps original image)'
			)
		),
		'By keyword or hashtag' => array(
			'q' => array(
				'name' => 'Keyword or #hashtag',
				'required' => true,
				'exampleValue' => 'rss-bridge, #rss-bridge',
				'title' => <<<EOD
* To search for multiple words (must contain all of these words), put a space between them.

Example: `rss-bridge release`.

* To search for multiple words (contains any of these words), put "OR" between them.

Example: `rss-bridge OR rssbridge`.

* To search for an exact phrase (including whitespace), put double-quotes around them.

Example: `"rss-bridge release"`

* If you want to search for anything **but** a specific word, put a hyphen before it.

Example: `rss-bridge -release` (ignores "release")

* Of course, this also works for hashtags.

Example: `#rss-bridge OR #rssbridge`

* And you can combine them in any shape or form you like.

Example: `#rss-bridge OR #rssbridge -release`
EOD
			)
		),
		'By username' => array(
			'u' => array(
				'name' => 'username',
				'required' => true,
				'exampleValue' => 'sebsauvage',
				'title' => 'Insert a user name'
			),
			'norep' => array(
				'name' => 'Without replies',
				'type' => 'checkbox',
				'title' => 'Only return initial tweets'
			),
			'noretweet' => array(
				'name' => 'Without retweets',
				'required' => false,
				'type' => 'checkbox',
				'title' => 'Hide retweets'
			),
			'nopinned' => array(
				'name' => 'Without pinned tweet',
				'required' => false,
				'type' => 'checkbox',
				'title' => 'Hide pinned tweet'
			)
		),
		'By list' => array(
			'user' => array(
				'name' => 'User',
				'required' => true,
				'exampleValue' => 'sebsauvage',
				'title' => 'Insert a user name'
			),
			'list' => array(
				'name' => 'List',
				'required' => true,
				'title' => 'Insert the list name'
			),
			'filter' => array(
				'name' => 'Filter',
				'exampleValue' => '#rss-bridge',
				'required' => false,
				'title' => 'Specify term to search for'
			)
		),
		'By list ID' => array(
			'listid' => array(
				'name' => 'List ID',
				'exampleValue' => '31748',
				'required' => true,
				'title' => 'Insert the list id'
			),
			'filter' => array(
				'name' => 'Filter',
				'exampleValue' => '#rss-bridge',
				'required' => false,
				'title' => 'Specify term to search for'
			)
		)
	);

	private $apiKey     = null;
	private $guestToken = null;
	private $authHeader = array();

	public function detectParameters($url){
		$params = array();

		// By keyword or hashtag (search)
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/search.*(\?|&)q=([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = urldecode($matches[4]);
			return $params;
		}

		// By hashtag
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/hashtag\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = urldecode($matches[3]);
			return $params;
		}

		// By list
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)\/lists\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['user'] = urldecode($matches[3]);
			$params['list'] = urldecode($matches[4]);
			return $params;
		}

		// By username
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['u'] = urldecode($matches[3]);
			return $params;
		}

		return null;
	}

	public function getName(){
		switch($this->queriedContext) {
		case 'By keyword or hashtag':
			$specific = 'search ';
			$param = 'q';
			break;
		case 'By username':
			$specific = '@';
			$param = 'u';
			break;
		case 'By list':
			return $this->getInput('list') . ' - Twitter list by ' . $this->getInput('user');
		case 'By list ID':
			return 'Twitter List #' . $this->getInput('listid');
		default: return parent::getName();
		}
		return 'Twitter ' . $specific . $this->getInput($param);
	}

	public function getURI(){
		switch($this->queriedContext) {
		case 'By keyword or hashtag':
			return self::URI
			. 'search?q='
			. urlencode($this->getInput('q'))
			. '&f=tweets';
		case 'By username':
			return self::URI
			. urlencode($this->getInput('u'));
			// Always return without replies!
			// . ($this->getInput('norep') ? '' : '/with_replies');
		case 'By list':
			return self::URI
			. urlencode($this->getInput('user'))
			. '/lists/'
			. str_replace(' ', '-', strtolower($this->getInput('list')));
		case 'By list ID':
			return self::URI
			. 'i/lists/'
			. urlencode($this->getInput('listid'));
		default: return parent::getURI();
		}
	}

	public function collectData(){
		// $data will contain an array of all found tweets (unfiltered)
		$data = null;
		// Contains user data (when in by username context)
		$user = null;
		// Array of all found tweets
		$tweets = array();

		// Get authentication information
		$this->getApiKey();

		// Try to get all tweets
		switch($this->queriedContext) {
		case 'By username':
			$user = $this->makeApiCall('/1.1/users/show.json', array('screen_name' => $this->getInput('u')));
			if (!$user) {
				returnServerError('Requested username can\'t be found.');
			}

			$params = array(
				'user_id'       => $user->id_str,
				'tweet_mode'    => 'extended'
			);

			$data = $this->makeApiCall('/1.1/statuses/user_timeline.json', $params);
			break;

		case 'By keyword or hashtag':
			$params = array(
				'q'                 => urlencode($this->getInput('q')),
				'tweet_mode'        => 'extended',
				'tweet_search_mode' => 'live',
			);

			$data = $this->makeApiCall('/1.1/search/tweets.json', $params)->statuses;
			break;

		case 'By list':
			$params = array(
				'slug'              => strtolower($this->getInput('list')),
				'owner_screen_name' => strtolower($this->getInput('user')),
				'tweet_mode'        => 'extended',
			);

			$data = $this->makeApiCall('/1.1/lists/statuses.json', $params);
			break;

		case 'By list ID':
			$params = array(
				'list_id'           => $this->getInput('listid'),
				'tweet_mode'        => 'extended',
			);

			$data = $this->makeApiCall('/1.1/lists/statuses.json', $params);
			break;

		default:
			returnServerError('Invalid query context !');
		}

		if(!$data) {
			switch($this->queriedContext) {
			case 'By keyword or hashtag':
				returnServerError('No results for this query.');
			case 'By username':
				returnServerError('Requested username can\'t be found.');
			case 'By list':
				returnServerError('Requested username or list can\'t be found');
			}
		}

		// Filter out unwanted tweets
		foreach ($data as $tweet) {
			// Filter out retweets to remove possible duplicates of original tweet
			switch($this->queriedContext) {
			case 'By keyword or hashtag':
				if (isset($tweet->retweeted_status) && substr($tweet->full_text, 0, 4) === 'RT @') {
					continue 2;
				}
				break;
			}
			$tweets[] = $tweet;
		}

		$hidePictures = $this->getInput('nopic');

		// $promotedTweetIds = array_reduce($data->timeline->instructions[0]->addEntries->entries, function($carry, $entry) {
		//    if (!isset($entry->content->item)) {
		//        return $carry;
		//    }
		//    $tweet = $entry->content->item->content->tweet;
		//    if (isset($tweet->promotedMetadata)) {
		//        $carry[] = $tweet->id;
		//    }
		//    return $carry;
		// }, array());

		$hidePinned = $this->getInput('nopinned');
		if ($hidePinned) {
			$pinnedTweetId = null;
			if ($user && $user->pinned_tweet_ids_str) {
				$pinnedTweetId = $user->pinned_tweet_ids_str;
			}
		}

		// Extract tweets from timeline property when in username mode
		// This fixes number of issues:
		// * If there's a retweet of a quote tweet, the quoted tweet will not appear in results (since it wasn't retweeted directly)
		// * Pinned tweets do not get stuck at the bottom
		// if ($this->queriedContext === 'By username') {
		//	foreach($data->timeline->instructions[0]->addEntries->entries as $tweet) {
		//		if (!isset($tweet->content->item)) continue;
		//		$tweetId = $tweet->content->item->content->tweet->id;
		//		$selectedTweet = $this->getTweet($tweetId, $data->globalObjects);
		//		if (!$selectedTweet) continue;
		//		// If this is a retweet, it will contain shorter text and will point to the original full tweet (retweeted_status_id_str).
		//		// Let's use the original tweet text.
		//		if (isset($selectedTweet->retweeted_status_id_str)) {
		//			$tweetId = $selectedTweet->retweeted_status_id_str;
		//			$selectedTweet = $this->getTweet($tweetId, $data->globalObjects);
		//			if (!$selectedTweet) continue;
		//		}
		//		// use $tweetId as key to avoid duplicates (e.g. user retweeting their own tweet)
		//		$tweets[$tweetId] = $selectedTweet;
		//	}
		// } else {
		//	foreach($data->globalObjects->tweets as $tweet) {
		//		$tweets[] = $tweet;
		//	}
		// }

		// Create output array with all required elements for each tweet
		foreach($tweets as $tweet) {

			/* Debug::log('>>> ' . json_encode($tweet)); */
			// Skip spurious retweets
			// if (isset($tweet->retweeted_status) && substr($tweet->text, 0, 4) === 'RT @') {
			//	continue;
			// }

			// // Skip promoted tweets
			// if (in_array($tweet->id_str, $promotedTweetIds)) {
			//	continue;
			// }

			// // Skip pinned tweet
			if ($hidePinned && $tweet->id_str === $pinnedTweetId) {
				continue;
			}

			switch($this->queriedContext) {
				case 'By username':
					if ($this->getInput('norep') && isset($tweet->in_reply_to_status_id))
						continue 2;
					break;
			}

			$item = array();

			$realtweet = $tweet;
			if (isset($tweet->retweeted_status)) {
				// Tweet is a Retweet, so set author based on original tweet and set realtweet for reference to the right content
				$realtweet = $tweet->retweeted_status;
			}

			$item['username']  = $realtweet->user->screen_name;
			$item['fullname']  = $realtweet->user->name;
			$item['avatar']    = $realtweet->user->profile_image_url_https;
			$item['timestamp'] = $realtweet->created_at;
			$item['id']        = $realtweet->id_str;
			$item['uri']       = self::URI . $item['username'] . '/status/' . $item['id'];
			$item['author']    = (isset($tweet->retweeted_status) ? 'RT: ' : '' )
						 . $item['fullname']
						 . ' (@'
						 . $item['username'] . ')';

			// Convert plain text URLs into HTML hyperlinks
			$fulltext = $realtweet->full_text;
			$cleanedTweet = $fulltext;

			$foundUrls = false;

			if (substr($cleanedTweet, 0, 4) === 'RT @') {
				$cleanedTweet = substr($cleanedTweet, 3);
			}

			if (isset($realtweet->entities->media)) {
				foreach($realtweet->entities->media as $media) {
					$cleanedTweet = str_replace($media->url,
						'<a href="' . $media->expanded_url . '">' . $media->display_url . '</a>',
						$cleanedTweet);
					$foundUrls = true;
				}
			}
			if (isset($realtweet->entities->urls)) {
				foreach($realtweet->entities->urls as $url) {
					$cleanedTweet = str_replace($url->url,
						'<a href="' . $url->expanded_url . '">' . $url->display_url . '</a>',
						$cleanedTweet);
					$foundUrls = true;
				}
			}
			if ($foundUrls === false) {
				// fallback to regex'es
				$reg_ex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
				if(preg_match($reg_ex, $realtweet->full_text, $url)) {
					$cleanedTweet = preg_replace($reg_ex,
						"<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ",
						$cleanedTweet);
				}
			}
			// generate the title
			$item['title'] = strip_tags($cleanedTweet);

			// Add avatar
			$picture_html = '';
			if(!$hidePictures) {
				$picture_html = <<<EOD
<a href="https://twitter.com/{$item['username']}">
<img
	style="align:top; width:75px; border:1px solid black;"
	alt="{$item['username']}"
	src="{$item['avatar']}"
	title="{$item['fullname']}" />
</a>
EOD;
			}

			// Get images
			$media_html = '';
			if(isset($realtweet->extended_entities->media) && !$this->getInput('noimg')) {
				foreach($realtweet->extended_entities->media as $media) {
					switch($media->type) {
					case 'photo':
						$image = $media->media_url_https . '?name=orig';
						$display_image = $media->media_url_https;
						// add enclosures
						$item['enclosures'][] = $image;

						$media_html .= <<<EOD
<a href="{$image}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	referrerpolicy="no-referrer"
	src="{$display_image}" />
</a>
EOD;
						break;
					case 'video':
					case 'animated_gif':
						if(isset($media->video_info)) {
							$link = $media->expanded_url;
							$poster = $media->media_url_https;
							$video = null;
							$maxBitrate = -1;
							foreach($media->video_info->variants as $variant) {
								$bitRate = isset($variant->bitrate) ? $variant->bitrate : -100;
								if ($bitRate > $maxBitrate) {
									$maxBitrate = $bitRate;
									$video = $variant->url;
								}
							}
							if(!is_null($video)) {
								// add enclosures
								$item['enclosures'][] = $video;
								$item['enclosures'][] = $poster;

								$media_html .= <<<EOD
<a href="{$link}">Video</a>
<video
	style="align:top; max-width:558px; border:1px solid black;"
	referrerpolicy="no-referrer"
	src="{$video}" poster="{$poster}" />
EOD;
							}
						}
						break;
					default:
						Debug::log('Missing support for media type: ' . $media->type);
					}
				}
			}

			switch($this->queriedContext) {
				case 'By list':
				case 'By list ID':
					// Check if filter applies to list (using raw content)
					if($this->getInput('filter')) {
						if(stripos($cleanedTweet, $this->getInput('filter')) === false) {
							continue 2; // switch + for-loop!
						}
					}
					break;
				case 'By username':
					if ($this->getInput('noretweet') && strtolower($item['username']) != strtolower($this->getInput('u'))) {
						continue 2; // switch + for-loop!
					}
					break;
				default:
			}

			$item['content'] = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	{$picture_html}
</div>
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedTweet}</blockquote>
</div>
<div style="display: block; vertical-align: top;">
	<blockquote>{$media_html}</blockquote>
</div>
EOD;

			$item['content'] = htmlspecialchars_decode($item['content'], ENT_QUOTES);

			// put out
			$this->items[] = $item;
		}

		usort($this->items, array('TwitterBridge', 'compareTweetId'));
	}

	private static function compareTweetId($tweet1, $tweet2) {
		return (intval($tweet1['id']) < intval($tweet2['id']) ? 1 : -1);
	}

	//The aim of this function is to get an API key and a guest token
	//This function takes 2 requests, and therefore is cached
	private function getApiKey($forceNew = 0) {

		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$r_cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$r_cache->setScope(get_called_class());
		$r_cache->setKey(array('refresh'));
		$data = $r_cache->loadData();

		$refresh = null;
		if($data === null) {
			$refresh = time();
			$r_cache->saveData($refresh);
		} else {
			$refresh = $data;
		}

		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey(array('api_key'));
		$data = $cache->loadData();

		$apiKey = null;
		if($forceNew || $data === null || (time() - $refresh) > self::GUEST_TOKEN_EXPIRY) {
			$twitterPage = getContents('https://twitter.com');

			$jsLink = false;
			$jsMainRegexArray = array(
				'/(https:\/\/abs\.twimg\.com\/responsive-web\/web\/main\.[^\.]+\.js)/m',
				'/(https:\/\/abs\.twimg\.com\/responsive-web\/web_legacy\/main\.[^\.]+\.js)/m',
				'/(https:\/\/abs\.twimg\.com\/responsive-web\/client-web\/main\.[^\.]+\.js)/m',
				'/(https:\/\/abs\.twimg\.com\/responsive-web\/client-web-legacy\/main\.[^\.]+\.js)/m',
			);
			foreach ($jsMainRegexArray as $jsMainRegex) {
				if (preg_match_all($jsMainRegex, $twitterPage, $jsMainMatches, PREG_SET_ORDER, 0)) {
					$jsLink = $jsMainMatches[0][0];
					break;
				}
			}
			if (!$jsLink) {
				 returnServerError('Could not locate main.js link');
			}

			$jsContent = getContents($jsLink);
			$apiKeyRegex = '/([a-zA-Z0-9]{59}%[a-zA-Z0-9]{44})/m';
			preg_match_all($apiKeyRegex, $jsContent, $apiKeyMatches, PREG_SET_ORDER, 0);
			$apiKey = $apiKeyMatches[0][0];
			$cache->saveData($apiKey);
		} else {
			$apiKey = $data;
		}

		$cacheFac2 = new CacheFactory();
		$cacheFac2->setWorkingDir(PATH_LIB_CACHES);
		$gt_cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$gt_cache->setScope(get_called_class());
		$gt_cache->setKey(array('guest_token'));
		$guestTokenUses = $gt_cache->loadData();

		$guestToken = null;
		if($forceNew || $guestTokenUses === null || !is_array($guestTokenUses) || count($guestTokenUses) != 2
		|| $guestTokenUses[0] <= 0 || (time() - $refresh) > self::GUEST_TOKEN_EXPIRY) {
			$guestToken = $this->getGuestToken($apiKey);
			if ($guestToken === null) {
				if($guestTokenUses === null) {
					returnServerError('Could not parse guest token');
				} else {
					$guestToken = $guestTokenUses[1];
				}
			} else {
				$gt_cache->saveData(array(self::GUEST_TOKEN_USES, $guestToken));
				$r_cache->saveData(time());
			}
		} else {
			$guestTokenUses[0] -= 1;
			$gt_cache->saveData($guestTokenUses);
			$guestToken = $guestTokenUses[1];
		}

		$this->apiKey	   = $apiKey;
		$this->guestToken  = $guestToken;
		$this->authHeaders = array(
			'authorization: Bearer ' . $apiKey,
			'x-guest-token: ' . $guestToken,
		);

		return array($apiKey, $guestToken);
	}

	// Get a guest token. This is different to an API key,
	// and it seems to change more regularly than the API key.
	private function getGuestToken($apiKey) {
		$headers = array(
			'authorization: Bearer ' . $apiKey,
		);
		$opts = array(
			CURLOPT_POST => 1,
		);

		try {
			$pageContent = getContents('https://api.twitter.com/1.1/guest/activate.json', $headers, $opts, true);
			$guestToken = json_decode($pageContent['content'])->guest_token;
		} catch (Exception $e) {
			$guestToken = null;
		}
		return $guestToken;
	}

	/**
	 * Tries to make an API call to twitter.
	 * @param $api string API entry point
	 * @param $params array additional URI parmaeters
	 * @return object json data
	 */
	private function makeApiCall($api, $params) {
		$uri = self::API_URI . $api . '?' . http_build_query($params);

		$retries = 1;
		$retry = 0;
		do {
			$retry = 0;

			$result = $this->getContents($uri, $this->authHeaders, array(), true);

			switch($result['errorcode']) {
			case 200: // Contents OK
			case 201: // Contents Created
			case 202: // Contents Accepted
				break;

			case 401:
			case 403:
				if ($retries) {
					$retries--;
					$retry = 1;
					$this->getApiKey(1);
					continue 2;
				}
			default:
				$code = $result['errorcode'];
				$data = $result['content'];
				returnServerError(<<<EOD
Failed to make api call: $api
HTTP Status: $code
Errormessage: $data
EOD
				);
				break;
			}
		} while ($retry);

		$data = json_decode($result['content']);

		return $data;
	}

	private function getContents($url, $header = array(), $opts = array(), $returnHeader = false){
		Debug::log('Reading contents from "' . $url . '"');

		$retVal = array(
			'header' => '',
			'content' => '',
			'errorcode' => 0,
		);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if(is_array($header) && count($header) !== 0) {

			Debug::log('Setting headers: ' . json_encode($header));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		}

		curl_setopt($ch, CURLOPT_USERAGENT, ini_get('user_agent'));
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

		if(is_array($opts) && count($opts) !== 0) {

			Debug::log('Setting options: ' . json_encode($opts));

			foreach($opts as $key => $value) {
				curl_setopt($ch, $key, $value);
			}

		}

		if(defined('PROXY_URL') && !defined('NOPROXY')) {

			Debug::log('Setting proxy url: ' . PROXY_URL);
			curl_setopt($ch, CURLOPT_PROXY, PROXY_URL);

		}

		// We always want the response header as part of the data!
		curl_setopt($ch, CURLOPT_HEADER, true);

		// Enables logging for the outgoing header
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		$data = curl_exec($ch);
		$errorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$retVal['errorcode'] = $errorCode;

		$curlError = curl_error($ch);
		$curlErrno = curl_errno($ch);
		$curlInfo = curl_getinfo($ch);

		Debug::log('Outgoing header: ' . json_encode($curlInfo));
		if($data === false)
			Debug::log('Cant\'t download ' . $url . ' cUrl error: ' . $curlError . ' (' . $curlErrno . ')');

		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($data, 0, $headerSize);
		$retVal['header'] = $header;

		Debug::log('Response header: ' . $header);

		$headers = parseResponseHeader($header);

		$finalHeader = end($headers);

		curl_close($ch);

		$data = substr($data, $headerSize);
		$retVal['content'] = $data;

		return ($returnHeader === true) ? $retVal : $retVal['content'];
	}
}
