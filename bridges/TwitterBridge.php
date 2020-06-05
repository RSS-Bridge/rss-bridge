<?php
class TwitterBridge extends BridgeAbstract {
	const NAME = 'Twitter Bridge';
	const URI = 'https://twitter.com/';
	const API_URI = 'https://api.twitter.com';
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

	public function getApiURI() {
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

			$item['username'] = $user_info->name;
			$item['fullname'] = $user_info->screen_name;
			$item['author'] = $item['fullname'] . ' (@' . $item['username'] . ')';
			$item['avatar'] = $user_info->profile_image_url_https;

			$item['id'] = $tweet->id_str;
			$item['uri'] = self::URI . $tweet->user_id_str . '/status/' . $item['id'];
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
			$image_html = '';
			if(isset($tweet->extended_entities->media) && !$this->getInput('noimg')) {
				foreach($tweet->extended_entities->media as $media) {
					$image = $media->media_url_https;
					$display_image = $media->display_url;
					// add enclosures
					$item['enclosures'][] = $image;

					$image_html .= <<<EOD
<a href="{$image}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	src="{$display_image}" />
</a>
EOD;
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
	<blockquote>{$image_html}</blockquote>
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
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey(array('api_key'));
		$data = $cache->loadData();

		if($data === null) {
			$twitterPage = getContents('https://twitter.com');
			$jsMainRegex = '/(https:\/\/abs\.twimg\.com\/responsive-web\/web\/main\.[^\.]+\.js)/m';
			preg_match_all($jsMainRegex, $twitterPage, $jsMainMatches, PREG_SET_ORDER, 0);
			$jsLink = $jsMainMatches[0][0];
			$guestTokenRegex = '/gt=([0-9]*)/m';
			preg_match_all($guestTokenRegex, $twitterPage, $guestTokenMatches, PREG_SET_ORDER, 0);
			$guestToken = $guestTokenMatches[0][1];

			$jsContent = getContents($jsLink);
			$apiKeyRegex = '/([a-zA-Z0-9]{59}%[a-zA-Z0-9]{44})/m';
			preg_match_all($apiKeyRegex, $jsContent, $apiKeyMatches, PREG_SET_ORDER, 0);
			$apiKey = $apiKeyMatches[0][0];
			$cache->saveData(array($apiKey, $guestToken));
			return array($apiKey, $guestToken);
		}

		return $data;

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
