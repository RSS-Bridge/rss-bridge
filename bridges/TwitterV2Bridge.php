<?php
/**
 * TwitterV2Bridge leverages Twitter V2 API
 *
 * The V1.1 API, at least when using the standard public dummy Bearer Key,
 * sometimes omits tweets containing "Sensitive Content".
 *
 * To use this bridge, you must:
 * 1. Sign up for a Twitter Developer account
 * 2. Create a new Project and App
 * 3. Generate a Bearer Token in the App
 * 4. Put that Bearer Token into config.ini.php
 *
 * If config.ini.php does not exist in the rss-bridge root,
 * create it by copying config.default.ini.php
 *
 * Add a new section to config.ini.php:
 * [TwitterV2Bridge]
 * twitterv2apitoken = "<Your Bearer Token>"
 *
 */
class TwitterV2Bridge extends BridgeAbstract {
	const NAME = 'Twitter V2 Bridge';
	const URI = 'https://twitter.com/';
	const API_URI = 'https://api.twitter.com/2';
	const DESCRIPTION = 'returns tweets (using Twitter V2 API)';
	const MAINTAINER = 'quickwick';
	const CONFIGURATION = array(
		'twitterv2apitoken' => array(
			'required' => true,
		)
	);
	const PARAMETERS = array(
		'global' => array(
			'maxresults' => array(
				'name' => 'Maximum results',
				'required' => false,
				'exampleValue' => '20',
				'title' => 'Maximum number of tweets to retrieve'
			),
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
		'By keyword or hashtag' => array(
			'query' => array(
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
		'By list ID' => array(
			'listid' => array(
				'name' => 'List ID',
				'exampleValue' => '31748',
				'required' => true,
				'title' => 'Insert the list id'
			),
			'filter' => array(
				'name' => 'Filter',
				'exampleValue' => 'rss-bridge',
				'required' => false,
				'title' => 'Specify a single term to search for'
			)
		)
	);

	private $apiToken     = null;
	private $authHeaders = array();

	public function getName() {
		switch($this->queriedContext) {
			case 'By keyword or hashtag':
				$specific = 'search ';
				$param = 'query';
				break;
			case 'By username':
				$specific = '@';
				$param = 'u';
				break;
			case 'By list ID':
				return 'Twitter List #' . $this->getInput('listid');
			default:
				return parent::getName();
		}
		return 'Twitter ' . $specific . $this->getInput($param);
	}

	public function collectData() {
		// $data will contain an array of all found tweets
		$data = null;
		// Contains user data (when in by username context)
		$user = null;
		// Array of all found tweets
		$tweets = array();

		$hideProfilePic = $this->getInput('nopic');
		$hideImages = $this->getInput('noimg');
		$hideReplies = $this->getInput('norep');
		$hideRetweets = $this->getInput('noretweet');
		$hidePinned = $this->getInput('nopinned');
		$maxResults = $this->getInput('maxresults');

		// Read API token from config.ini.php, put into Header
		$this->apiToken     = $this->getOption('twitterv2apitoken');
		$this->authHeaders = array(
			'authorization: Bearer ' . $this->apiToken,
		);

		// Try to get all tweets
		switch($this->queriedContext) {
		case 'By username':
			//Get id from username
			$params = array(
				'user.fields'	=> 'pinned_tweet_id,profile_image_url'
			);
			$user = $this->makeApiCall('/users/by/username/'
			. $this->getInput('u'), $params);
			//Debug::log('User JSON: ' . json_encode($user));
			
			if(!$user) {
				returnServerError('Requested username can\'t be found.');
			}

			// Set default params
			$params = array(
				'max_results'	=> (empty($maxResults) ? '10' : $maxResults ),
				'tweet.fields'  =>
				'created_at,referenced_tweets,entities,attachments',
				'user.fields'	=> 'pinned_tweet_id',
				'expansions'	=>
				'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
				'media.fields'	=> 'type,url,preview_image_url'
			);
			
			// Set params to filter out replies and/or retweets
			if($hideReplies && $hideRetweets) {
				$params['exclude'] = 'replies,retweets';
			} elseif($hideReplies) {
				$params['exclude'] = 'replies';
			} elseif($hideRetweets) {
				$params['exclude'] = 'retweets';
			}

			// Get the tweets
			$data = $this->makeApiCall('/users/' . $user->data->id
			. '/tweets', $params);
			break;

		case 'By keyword or hashtag':
			$params = array(
				'query'			=> $this->getInput('query'),
				'max_results'	=> (empty($maxResults) ? '10' : $maxResults ),
				'tweet.fields'	=>
				'created_at,referenced_tweets,entities,attachments',
				'expansions'	=>
				'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
				'media.fields'	=> 'type,url,preview_image_url'
			);

			$data = $this->makeApiCall('/tweets/search/recent', $params);
			break;

		case 'By list ID':
			// Set default params
			$params = array(
				'max_results' => (empty($maxResults) ? '10' : $maxResults ),
				'tweet.fields' =>
				'created_at,referenced_tweets,entities,attachments',
				'expansions' =>
				'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
				'media.fields'	=> 'type,url,preview_image_url'
			);

			$data = $this->makeApiCall('/lists/' . $this->getInput('listid') .
			'/tweets', $params);
			break;

		default:
			returnServerError('Invalid query context !');
		}

		if(!$data) {
			switch($this->queriedContext) {
			case 'By keyword or hashtag':
				returnServerError('No results for this query.');
			case 'By username':
				returnServerError('Requested username cannnot be found.');
			case 'By list ID':
				returnServerError('Requested list cannnot be found');
			}
		}

		// figure out the Pinned Tweet Id
		if($hidePinned) {
			$pinnedTweetId = null;
			if(isset($user) && isset($user->data->pinned_tweet_id)) {
				$pinnedTweetId = $user->data->pinned_tweet_id;
			}
		}

		// Extract Media data into array
		isset($data->includes->media) ? $includesMedia = $data->includes->media : $includesMedia = null;

		// Extract additional Users data into array
		isset($data->includes->users) ? $includesUsers = $data->includes->users : $includesUsers = null;
		//Debug::log('Tweets Users JSON: ' . json_encode($includesUsers));

		// Extract additional Tweets data into array
		isset($data->includes->tweets) ? $includesTweets = $data->includes->tweets : $includesTweets = null;
		//Debug::log('Includes Tweets JSON: ' . json_encode($includesTweets));
		
		// Extract main Tweets data into array
		$tweets = $data->data;
		//Debug::log('Tweets JSON: ' . json_encode($tweets));

		// Make another API call to get user and media info for retweets
		// Is there some way to get this info included in original API call?
		$retweetedData = null;
		$retweetedMedia = null;
		$retweetedUsers = null;
		if(!$hideImages && !$hideRetweets && isset($includesTweets)) {
			// There has to be a better PHP way to extract the tweet Ids?
			$includesTweetsIds = array();
			foreach($includesTweets as $includesTweet) {
				$includesTweetsIds[] = $includesTweet->id;
			}
			//Debug::log('includesTweetsIds: ' . join(",",$includesTweetsIds));

			// Set default params for API query
			$params = array(
				'ids'			=> join(',',$includesTweetsIds),
				'tweet.fields'  => 'entities,attachments',
				'expansions'	=> 'author_id,attachments.media_keys',
				'media.fields'	=> 'type,url,preview_image_url',
				'user.fields'	=> 'id,profile_image_url'
			);

			// Get the retweeted tweets
			$retweetedData = $this->makeApiCall('/tweets', $params);

			$retweetedMedia = $retweetedData->includes->media;
			$retweetedUsers = $retweetedData->includes->users;
		}

		// Create output array with all required elements for each tweet
		foreach($tweets as $tweet) {
			//Debug::log('Tweet JSON: ' . json_encode($tweet));

			// Skip pinned tweet
			if($hidePinned && $tweet->id === $pinnedTweetId) {
				continue;
			}

			// Check if Retweet
			$isRetweet = false;
			if(isset($tweet->referenced_tweets)) {
				if($tweet->referenced_tweets[0]->type === 'retweeted') {
					$isRetweet = true;
				}
			}

			// Initialize empty array to hold eventual HTML output
			$item = array();

			// Start setting values needed for HTML output
			if($isRetweet || is_null($user)) {
				// Replace tweet object with original retweeted object
				if($isRetweet) {
					foreach($includesTweets as $includesTweet) {
						//Debug::log('Includes Tweet JSON: ' . json_encode($includesTweet));
						if($includesTweet->id === $tweet->referenced_tweets[0]->id) {
							$tweet = $includesTweet;
							break;
						}
					}
				}

				// Skip self-Retweets (can cause duplicate entries in output)
				if(isset($user) && $tweet->author_id === $user->data->id) {
					continue;
				}
				
				// Get user object for retweeted tweet
				$originalUser = new stdClass(); // make the linters stop complaining
				if(isset($retweetedUsers)) {
					foreach($retweetedUsers as $retweetedUser) {
						if($retweetedUser->id === $tweet->author_id) {
							$originalUser = $retweetedUser;
							break;
						}
					}
				}
				if(isset($includesUsers)) {
					foreach($includesUsers as $includesUser) {
						if($includesUser->id === $tweet->author_id) {
							$originalUser = $includesUser;
							break;
						}
					}
				}
				
				$item['username']  = $originalUser->username;
				$item['fullname']  = $originalUser->name;
				if(isset($originalUser->profile_image_url)) {
					$item['avatar']    = $originalUser->profile_image_url;	
				}
				else{
					$item['avatar'] = null;
				}
			}
			else{
				$item['username']  = $user->data->username;
				$item['fullname']  = $user->data->name;	
				$item['avatar']    = $user->data->profile_image_url;
			}
			$item['id']        = $tweet->id;
			$item['timestamp'] = $tweet->created_at;
			$item['uri']       =
			self::URI . $item['username'] . '/status/' . $item['id'];
			$item['author']    = ($isRetweet ? 'RT: ' : '' )
						 . $item['fullname']
						 . ' (@'
						 . $item['username'] . ')';

			// Convert plain text URLs into HTML hyperlinks
			$cleanedTweet = $tweet->text;
			//Debug::log('cleanedTweet: ' . $cleanedTweet);

			// Remove 'RT @' from tweet text
			// To Do: also remove the full username being retweeted?
			if(substr($cleanedTweet, 0, 4) === 'RT @') {
				$cleanedTweet = substr($cleanedTweet, 3);
			}

			// Perform filtering (skip some tweets)
			switch($this->queriedContext) {
				case 'By list ID':
					// Check if list tweet contains desired filter keyword
					// (using raw content)
					if($this->getInput('filter')) {
						if(stripos($cleanedTweet,
						$this->getInput('filter')) === false) {
							continue 2; // switch + for-loop!
						}
					}
					break;
				case 'By username':
					/* This section should be unnecessary, let's confirm
					if($hideRetweets && strtolower($item['username']) !=
					strtolower($this->getInput('u'))) {
						continue 2; // switch + for-loop!
					}
					break;
					*/
				default:
			}

			// Search for and replace URLs in Tweet text
			$foundUrls = false;
			if(isset($tweet->entities->urls)) {
				foreach($tweet->entities->urls as $url) {
					$cleanedTweet = str_replace($url->url,
						'<a href="' . $url->expanded_url
						. '">' . $url->display_url . '</a>',
						$cleanedTweet);
					$foundUrls = true;
				}
			}
			if($foundUrls === false) {
				// fallback to regex'es
				$reg_ex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
				if(preg_match($reg_ex, $cleanedTweet, $url)) {
					$cleanedTweet = preg_replace($reg_ex,
						"<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ",
						$cleanedTweet);
				}
			}

			// generate the title
			$item['title'] = strip_tags($cleanedTweet);

			// Add avatar
			$picture_html = '';
			if(!$hideProfilePic && isset($item['avatar'])) {
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
			if(!$hideImages && isset($tweet->attachments->media_keys)) {

				// Match media_keys in tweet to media list from, put matches
				// into new array
				$tweetMedia = array();
				// Start by checking the original list of tweet Media includes
				if(isset($includesMedia)) {
					foreach($includesMedia as $includesMedium) {
						if(in_array ($includesMedium->media_key,
						$tweet->attachments->media_keys)) {
							$tweetMedia[] = $includesMedium;
						}
					}
				}
				// If no matches found, check the retweet Media includes
				if(empty($tweetMedia) && isset($retweetedMedia)) {
					foreach($retweetedMedia as $retweetedMedium) {
						if(in_array ($retweetedMedium->media_key,
						$tweet->attachments->media_keys)) {
							$tweetMedia[] = $retweetedMedium;
						}
					}
				}

				foreach($tweetMedia as $media) {
					switch($media->type) {
					case 'photo':
						$image = $media->url . '?name=orig';
						if ($this->getInput('noimgscaling')){
							$display_image = $media->url;
						}
						else{
							$display_image = $media->url . '?name=thumb';
						}
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
						// To Do: Is there a way to easily match this
						// to a URL for a link?
						$display_image = $media->preview_image_url;

						$media_html .= <<<EOD
<img
	style="align:top; max-width:558px; border:1px solid black;"
	referrerpolicy="no-referrer"
	src="{$display_image}" />
EOD;
						break;
					case 'animated_gif':
						// To Do: Is there a way to easily match this to a
						// URL for a link?
						$display_image = $media->preview_image_url;

						$media_html .= <<<EOD
<img
	style="align:top; max-width:558px; border:1px solid black;"
	referrerpolicy="no-referrer"
	src="{$display_image}" />
EOD;
						break;
					default:
						Debug::log('Missing support for media type: '
						. $media->type);
					}
				}
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

		// Sort all tweets in array by date
		usort($this->items, array('TwitterV2Bridge', 'compareTweetDate'));
	}

	private static function compareTweetDate($tweet1, $tweet2) {
		return (strtotime($tweet1['timestamp']) < strtotime($tweet2['timestamp']) ? 1 : -1);
	}

	/**
	 * Tries to make an API call to Twitter.
	 * @param $api string API entry point
	 * @param $params array additional URI parmaeters
	 * @return object json data
	 */
	private function makeApiCall($api, $params) {
		if($params) {
			$uri = self::API_URI . $api . '?' . http_build_query($params);
		}
		else{
			$uri = self::API_URI . $api;
		}

		$result = $this->getContents($uri, $this->authHeaders, array(), true);

		switch($result['errorcode']) {
		case 200: // Contents OK
		case 201: // Contents Created
		case 202: // Contents Accepted
			break;
		case 401: // Unauthorized
		case 403: // Forbidden
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

		$data = json_decode($result['content']);
		return $data;
	}

	private function getContents($url, $header = array(), $opts = array(),
	$returnHeader = false) {
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
			Debug::log('Cant\'t download ' . $url . ' cUrl error: '
			. $curlError . ' (' . $curlErrno . ')');

		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($data, 0, $headerSize);
		$retVal['header'] = $header;

		Debug::log('Response header: ' . $header);

		$headers = parseResponseHeader($header);

		//$finalHeader = end($headers);

		curl_close($ch);

		$data = substr($data, $headerSize);
		$retVal['content'] = $data;

		return ($returnHeader === true) ? $retVal : $retVal['content'];
	}
}
