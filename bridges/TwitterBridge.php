<?php
class TwitterBridge extends BridgeAbstract {
	const NAME = 'Twitter Bridge';
	const URI = 'https://twitter.com/';
	const API_URI = 'https://api.twitter.com';
	const GUEST_TOKEN_USES = 100;
	const GUEST_TOKEN_EXPIRY = 300; // 5min
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'returns tweets';
	const MAINTAINER = 'pmaziere';
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
		)
	);

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
		default: return parent::getURI();
		}
	}

	private function getApiURI() {
		switch($this->queriedContext) {
		case 'By keyword or hashtag':
			return self::API_URI
			. '/2/search/adaptive.json?q='
			. urlencode($this->getInput('q'))
			. '&tweet_mode=extended';
		case 'By username':
			return self::API_URI
			. '/2/timeline/profile/'
			. $this->getRestId($this->getInput('u'))
			. '.json?tweet_mode=extended';
		case 'By list':
			return self::API_URI
			. '/2/timeline/list.json?list_id='
			. $this->getListId($this->getInput('user'), $this->getInput('list'))
			. '&tweet_mode=extended';
		default: returnServerError('Invalid query context !');
		}
	}

	public function collectData(){
		$html = '';
		$page = $this->getURI();

		$header = array(
			'User-Agent: Mozilla/5.0 (Windows NT 9.0; WOW64; Trident/7.0; rv:11.0) like Gecko'
		);

		$data = json_decode($this->getApiContents($this->getApiURI()));

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

		$hidePictures = $this->getInput('nopic');

		foreach($data->globalObjects->tweets as $tweet) {

			// Skip retweets?
			if($this->getInput('noretweet')
			&& isset($tweet->retweeted_status_id_str)) {
				continue;
			}

			$item = array();
			// extract username and sanitize
			$user_info = $this->getUserInformation($tweet->user_id_str, $data->globalObjects);

			$item['username'] = $user_info->screen_name;
			$item['fullname'] = $user_info->name;
			$item['author'] = $item['fullname'] . ' (@' . $item['username'] . ')';
			$item['avatar'] = $user_info->profile_image_url_https;

			$item['id'] = $tweet->id_str;
			$item['uri'] = self::URI . $item['username'] . '/status/' . $item['id'];
			// extract tweet timestamp
			$item['timestamp'] = $tweet->created_at;

			// generate the title
			$item['title'] = $tweet->full_text;
			$cleanedTweet  = $tweet->full_text;

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
			if(isset($tweet->extended_entities->media) && !$this->getInput('noimg')) {
				foreach($tweet->extended_entities->media as $media) {
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
					// Check if filter applies to list (using raw content)
					if($this->getInput('filter')) {
						if(stripos($cleanedTweet, $this->getInput('filter')) === false) {
							continue 2; // switch + for-loop!
						}
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
	private function getApiKey() {

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
		if($data === null || !is_array($data) || count($data) != 1) {
			$twitterPage = getContents('https://twitter.com');

			$jsMainRegex = '/(https:\/\/abs\.twimg\.com\/responsive-web\/web_legacy\/main\.[^\.]+\.js)/m';
			preg_match_all($jsMainRegex, $twitterPage, $jsMainMatches, PREG_SET_ORDER, 0);
			$jsLink = $jsMainMatches[0][0];

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
		if($guestTokenUses === null || !is_array($guestTokenUses) || count($guestTokenUses) != 2
		|| $guestTokenUses[0] <= 0 || (time() - $refresh) > self::GUEST_TOKEN_EXPIRY) {
			$guestToken = $this->getGuestToken();
			$gt_cache->saveData(array(self::GUEST_TOKEN_USES, $guestToken));
			$r_cache->saveData(time());
		} else {
			$guestTokenUses[0] -= 1;
			$gt_cache->saveData($guestTokenUses);
			$guestToken = $guestTokenUses[1];
		}

		return array($apiKey, $guestToken);

	}

	// Get a guest token. This is different to an API key,
	// and it seems to change more regularly than the API key.
	private function getGuestToken() {
		$pageContent = getContents('https://twitter.com', array(), array(), true);

		$guestTokenRegex = '/gt=([0-9]*)/m';
		preg_match_all($guestTokenRegex, $pageContent['header'], $guestTokenMatches, PREG_SET_ORDER, 0);
		if (!$guestTokenMatches)
				preg_match_all($guestTokenRegex, $pageContent['content'], $guestTokenMatches, PREG_SET_ORDER, 0);
		if (!$guestTokenMatches) returnServerError('Could not parse guest token');
		$guestToken = $guestTokenMatches[0][1];
		return $guestToken;
	}

	private function getApiContents($uri) {
		$apiKeys = $this->getApiKey();
		$headers = array('authorization: Bearer ' . $apiKeys[0],
				 'x-guest-token: ' . $apiKeys[1],
			   );
		return getContents($uri, $headers);
	}

	private function getRestId($username) {
		$searchparams = urlencode('{"screen_name":"' . strtolower($username) . '", "withHighlightedLabel":true}');
		$searchURL = self::API_URI . '/graphql/-xfUfZsnR_zqjFd-IfrN5A/UserByScreenName?variables=' . $searchparams;
		$searchResult = $this->getApiContents($searchURL);
		$searchResult = json_decode($searchResult);
		return $searchResult->data->user->rest_id;
	}

	private function getListId($username, $listName) {
		$searchparams = urlencode('{"screenName":"'
				. strtolower($username)
				. '", "listSlug": "'
				. $listName
				. '", "withHighlightedLabel":false}');
		$searchURL = self::API_URI . '/graphql/ErWsz9cObLel1BF-HjuBlA/ListBySlug?variables=' . $searchparams;
		$searchResult = $this->getApiContents($searchURL);
		$searchResult = json_decode($searchResult);
		return $searchResult->data->user_by_screen_name->list->id_str;
	}

	private function getUserInformation($userId, $apiData) {
		foreach($apiData->users as $user) {
			if($user->id_str == $userId) {
				return $user;
			}
		}
	}
}
