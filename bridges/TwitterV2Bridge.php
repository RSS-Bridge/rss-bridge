<?php

/**
 * TwitterV2Bridge leverages Twitter API v2, and requires
 * a unique API Bearer Token, which requires creation of
 * a Twitter Dev account. Link to instructions in DESCRIPTION.
 */
class TwitterV2Bridge extends BridgeAbstract
{
    const NAME = 'Twitter V2 Bridge';
    const URI = 'https://twitter.com/';
    const API_URI = 'https://api.twitter.com/2';
    const DESCRIPTION = 'Returns tweets (using Twitter API v2). See the 
	<a href="https://rss-bridge.github.io/rss-bridge/Bridge_Specific/TwitterV2.html">
	Configuration Instructions</a>.';
    const MAINTAINER = 'quickwick';
    const CONFIGURATION = [
        'twitterv2apitoken' => [
            'required' => true,
        ]
    ];
    const PARAMETERS = [
        'global' => [
            'filter' => [
                'name' => 'Filter',
                'exampleValue' => 'rss-bridge',
                'required' => false,
                'title' => 'Specify a single term to search for'
            ],
            'norep' => [
                'name' => 'Without replies',
                'type' => 'checkbox',
                'title' => 'Activate to exclude reply tweets'
            ],
            'noretweet' => [
                'name' => 'Without retweets',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Activate to exclude retweets'
            ],
            'nopinned' => [
                'name' => 'Without pinned tweet',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Activate to exclude pinned tweets'
            ],
            'maxresults' => [
                'name' => 'Maximum results',
                'required' => false,
                'exampleValue' => '20',
                'title' => 'Maximum number of tweets to retrieve (limit is 100)'
            ],
            'imgonly' => [
                'name' => 'Only media tweets',
                'type' => 'checkbox',
                'title' => 'Activate to show only tweets with media (photo/video)'
            ],
            'nopic' => [
                'name' => 'Hide profile pictures',
                'type' => 'checkbox',
                'title' => 'Activate to hide profile pictures in content'
            ],
            'noimg' => [
                'name' => 'Hide images in tweets',
                'type' => 'checkbox',
                'title' => 'Activate to hide images in tweets'
            ],
            'noimgscaling' => [
                'name' => 'Disable image scaling',
                'type' => 'checkbox',
                'title' => 'Activate to display original sized images (no thumbnails)'
            ],
            'idastitle' => [
                'name' => 'Use tweet id as title',
                'type' => 'checkbox',
                'title' => 'Activate to use tweet id as title (instead of tweet text)'
            ]
        ],
        'By username' => [
            'u' => [
                'name' => 'username',
                'required' => true,
                'exampleValue' => 'sebsauvage',
                'title' => 'Insert a user name'
            ]
        ],
        'By keyword or hashtag' => [
            'query' => [
                'name' => 'Keyword or #hashtag',
                'required' => true,
                'exampleValue' => 'rss-bridge OR #rss-bridge',
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
            ]
        ],
        'By list ID' => [
            'listid' => [
                'name' => 'List ID',
                'exampleValue' => '31748',
                'required' => true,
                'title' => 'Enter a list id'
            ]
        ]
    ];

    // $Item variable needs to be accessible from multiple functions without passing
    private $item = [];

    public function getName()
    {
        switch ($this->queriedContext) {
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

    public function collectData()
    {
        // $data will contain an array of all found tweets
        $data = null;
        // Contains user data (when in by username context)
        $user = null;
        // Array of all found tweets
        $tweets = [];

        $hideProfilePic = $this->getInput('nopic');
        $hideImages = $this->getInput('noimg');
        $hideReplies = $this->getInput('norep');
        $hideRetweets = $this->getInput('noretweet');
        $hidePinned = $this->getInput('nopinned');
        $tweetFilter = $this->getInput('filter');
        $maxResults = $this->getInput('maxresults');
        if ($maxResults > 100) {
            $maxResults = 100;
        }
        $idAsTitle = $this->getInput('idastitle');
        $onlyMediaTweets = $this->getInput('imgonly');

        // Read API token from config.ini.php, put into Header
        $apiToken = $this->getOption('twitterv2apitoken');
        $authHeaders = [
            'authorization: Bearer ' . $apiToken,
        ];

        // Try to get all tweets
        switch ($this->queriedContext) {
            case 'By username':
                //Get id from username
                $params = [
                'user.fields'   => 'pinned_tweet_id,profile_image_url'
                ];
                $user = $this->makeApiCall('/users/by/username/'
                . $this->getInput('u'), $authHeaders, $params);

                if (isset($user->errors)) {
                    Debug::log('User JSON: ' . json_encode($user));
                    returnServerError('Requested username can\'t be found.');
                }

                // Set default params
                $params = [
                'max_results'   => (empty($maxResults) ? '10' : $maxResults),
                'tweet.fields'
                => 'created_at,referenced_tweets,entities,attachments',
                'user.fields'   => 'pinned_tweet_id',
                'expansions'
                => 'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
                'media.fields'  => 'type,url,preview_image_url'
                ];

                // Set params to filter out replies and/or retweets
                if ($hideReplies && $hideRetweets) {
                    $params['exclude'] = 'replies,retweets';
                } elseif ($hideReplies) {
                    $params['exclude'] = 'replies';
                } elseif ($hideRetweets) {
                    $params['exclude'] = 'retweets';
                }

                // Get the tweets
                $data = $this->makeApiCall('/users/' . $user->data->id
                . '/tweets', $authHeaders, $params);
                break;

            case 'By keyword or hashtag':
                $params = [
                'query'         => $this->getInput('query'),
                'max_results'   => (empty($maxResults) ? '10' : $maxResults),
                'tweet.fields'
                => 'created_at,referenced_tweets,entities,attachments',
                'expansions'
                => 'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
                'media.fields'  => 'type,url,preview_image_url'
                ];

                // Set params to filter out replies and/or retweets
                if ($hideReplies) {
                    $params['query'] = $params['query'] . ' -is:reply';
                }
                if ($hideRetweets) {
                    $params['query'] = $params['query'] . ' -is:retweet';
                }

                $data = $this->makeApiCall('/tweets/search/recent', $authHeaders, $params);
                break;

            case 'By list ID':
                // Set default params
                $params = [
                'max_results' => (empty($maxResults) ? '10' : $maxResults),
                'tweet.fields'
                => 'created_at,referenced_tweets,entities,attachments',
                'expansions'
                => 'referenced_tweets.id.author_id,entities.mentions.username,attachments.media_keys',
                'media.fields'  => 'type,url,preview_image_url'
                ];

                $data = $this->makeApiCall('/lists/' . $this->getInput('listid') .
                '/tweets', $authHeaders, $params);
                break;

            default:
                returnServerError('Invalid query context !');
        }

        if (
            (isset($data->errors) && !isset($data->data)) ||
            (isset($data->meta) && $data->meta->result_count === 0)
        ) {
            Debug::log('Data JSON: ' . json_encode($data));
            switch ($this->queriedContext) {
                case 'By keyword or hashtag':
                    returnServerError('No results for this query.');
                    // fall-through
                case 'By username':
                    returnServerError('Requested username cannnot be found.');
                    // fall-through
                case 'By list ID':
                    returnServerError('Requested list cannnot be found');
                    // fall-through
            }
        }

        // figure out the Pinned Tweet Id
        if ($hidePinned) {
            $pinnedTweetId = null;
            if (isset($user) && isset($user->data->pinned_tweet_id)) {
                $pinnedTweetId = $user->data->pinned_tweet_id;
            }
        }

        // Extract Media data into array
        isset($data->includes->media) ? $includesMedia = $data->includes->media : $includesMedia = null;

        // Extract additional Users data into array
        isset($data->includes->users) ? $includesUsers = $data->includes->users : $includesUsers = null;

        // Extract additional Tweets data into array
        isset($data->includes->tweets) ? $includesTweets = $data->includes->tweets : $includesTweets = null;

        // Extract main Tweets data into array
        $tweets = $data->data;

        // Make another API call to get user and media info for retweets
        // Is there some way to get this info included in original API call?
        $retweetedData = null;
        $retweetedMedia = null;
        $retweetedUsers = null;
        if (!$hideImages && isset($includesTweets)) {
            // There has to be a better PHP way to extract the tweet Ids?
            $includesTweetsIds = [];
            foreach ($includesTweets as $includesTweet) {
                $includesTweetsIds[] = $includesTweet->id;
            }
            Debug::log('includesTweetsIds: ' . join(',', $includesTweetsIds));

            // Set default params for API query
            $params = [
                'ids'           => join(',', $includesTweetsIds),
                'tweet.fields'  => 'entities,attachments',
                'expansions'    => 'author_id,attachments.media_keys',
                'media.fields'  => 'type,url,preview_image_url',
                'user.fields'   => 'id,profile_image_url'
            ];

            // Get the retweeted tweets
            $retweetedData = $this->makeApiCall('/tweets', $authHeaders, $params);

            // Extract retweets Media data into array
            isset($retweetedData->includes->media) ? $retweetedMedia
            = $retweetedData->includes->media : $retweetedMedia = null;

            // Extract retweets additional Users data into array
            isset($retweetedData->includes->users) ? $retweetedUsers
            = $retweetedData->includes->users : $retweetedUsers = null;
        }

        // Create output array with all required elements for each tweet
        foreach ($tweets as $tweet) {
            //Debug::log('Tweet JSON: ' . json_encode($tweet));

            // Skip pinned tweet (if selected)
            if ($hidePinned && $tweet->id === $pinnedTweetId) {
                continue;
            }

            // Check if tweet is Retweet, Quote or Reply
            $isRetweet = false;
            $isReply = false;
            $isQuote = false;

            if (isset($tweet->referenced_tweets)) {
                switch ($tweet->referenced_tweets[0]->type) {
                    case 'retweeted':
                        $isRetweet = true;
                        break;
                    case 'quoted':
                        $isQuote = true;
                        break;
                    case 'replied_to':
                        $isReply = true;
                        break;
                }
            }

            // Skip replies and/or retweets (if selected). This check is primarily for lists
            // These should already be pre-filtered for username and keyword queries
            if (($hideRetweets && $isRetweet) || ($hideReplies && $isReply)) {
                continue;
            }

            // Initialize empty array to hold feed item values
            $this->item = [];

            // Start getting and setting values needed for HTML output
            $quotedTweet = null;
            $cleanedQuotedTweet = null;
            $quotedUser = null;
            if ($isQuote) {
                Debug::log('Tweet is quote');
                foreach ($includesTweets as $includesTweet) {
                    if ($includesTweet->id === $tweet->referenced_tweets[0]->id) {
                        $quotedTweet = $includesTweet;
                        $cleanedQuotedTweet = nl2br($quotedTweet->text);
                        //Debug::log('Found quoted tweet');
                        break;
                    }
                }

                $quotedUser = $this->getTweetUser($quotedTweet, $retweetedUsers, $includesUsers);
            }
            if ($isRetweet || is_null($user)) {
                Debug::log('Tweet is retweet, or $user is null');
                // Replace tweet object with original retweeted object
                if ($isRetweet) {
                    foreach ($includesTweets as $includesTweet) {
                        if ($includesTweet->id === $tweet->referenced_tweets[0]->id) {
                            $tweet = $includesTweet;
                            break;
                        }
                    }
                }

                // Skip self-Retweets (can cause duplicate entries in output)
                if (isset($user) && $tweet->author_id === $user->data->id) {
                    continue;
                }

                // Get user object for retweeted tweet
                $originalUser = $this->getTweetUser($tweet, $retweetedUsers, $includesUsers);

                $this->item['username']  = $originalUser->username;
                $this->item['fullname']  = $originalUser->name;
                if (isset($originalUser->profile_image_url)) {
                    $this->item['avatar']    = $originalUser->profile_image_url;
                } else {
                    $this->item['avatar'] = null;
                }
            } else {
                $this->item['username']  = $user->data->username;
                $this->item['fullname']  = $user->data->name;
                $this->item['avatar']    = $user->data->profile_image_url;
            }
            $this->item['id']        = $tweet->id;
            $this->item['timestamp'] = $tweet->created_at;
            $this->item['uri']
            = self::URI . $this->item['username'] . '/status/' . $this->item['id'];
            $this->item['author']    = ($isRetweet ? 'RT: ' : '')
                         . $this->item['fullname']
                         . ' (@'
                         . $this->item['username'] . ')';

            $cleanedTweet = nl2br($tweet->text);
            //Debug::log('cleanedTweet: ' . $cleanedTweet);

            // Perform optional keyword filtering (only keep tweet if keyword is found)
            if (! empty($tweetFilter)) {
                if (stripos($cleanedTweet, $this->getInput('filter')) === false) {
                    continue;
                }
            }

            // Perform optional non-media tweet skip
            // This check must wait until after retweets are identified
            if (
                $onlyMediaTweets && !isset($tweet->attachments->media_keys) &&
                (($isQuote && !isset($quotedTweet->attachments->media_keys)) || !$isQuote)
            ) {
                // There is no media in current tweet or quoted tweet, skip to next
                continue;
            }

            // Search for and replace URLs in Tweet text
            $cleanedTweet = $this->replaceTweetURLs($tweet, $cleanedTweet);
            if (isset($cleanedQuotedTweet)) {
                Debug::log('Replacing URLs in Quoted Tweet text');
                $cleanedQuotedTweet = $this->replaceTweetURLs($quotedTweet, $cleanedQuotedTweet);
            }

            // Generate Title text
            if ($idAsTitle) {
                $titleText = $tweet->id;
            } else {
                $titleText = strip_tags($cleanedTweet);
            }

            if ($isRetweet) {
                if (substr($titleText, 0, 4) === 'RT @') {
                    $titleText = substr_replace($titleText, ':', 2, 0);
                } else {
                    $titleText = 'RT: @' . $this->item['username'] . ': ' . $titleText;
                }
            } elseif ($isReply  && !$idAsTitle) {
                $titleText = 'R: ' . $titleText;
            }

            $this->item['title'] = $titleText;

            // Get external link info
            $extURL = null;
            if (isset($tweet->entities->urls) && strpos($tweet->entities->urls[0]->expanded_url, 'twitter.com') === false) {
                Debug::log('Found an external link!');
                $extURL = $tweet->entities->urls[0]->expanded_url;
                Debug::log($extURL);
                $extDisplayURL = $tweet->entities->urls[0]->display_url;
                $extTitle = $tweet->entities->urls[0]->title;
                $extDesc = $tweet->entities->urls[0]->description;
                if (isset($tweet->entities->urls[0]->images)) {
                    $extMediaOrig = $tweet->entities->urls[0]->images[0]->url;
                    $extMediaScaled = $tweet->entities->urls[0]->images[1]->url;
                } else {
                    $extMediaOrig = '';
                    $extMediaScaled = '';
                }
            }

            // Generate Avatar HTML block
            $picture_html = '';
            if (!$hideProfilePic && isset($this->item['avatar'])) {
                $picture_html = <<<EOD
<a href="https://twitter.com/{$this->item['username']}">
<img
	style="margin-right: 10px; margin-bottom: 10px;"
	alt="{$this->item['username']}"
	src="{$this->item['avatar']}"
	title="{$this->item['fullname']}" />
</a>
EOD;
            }

            // Generate media HTML block
            $media_html = '';
            $quoted_media_html = '';
            $ext_media_html = '';
            if (!$hideImages) {
                if (isset($tweet->attachments->media_keys)) {
                    Debug::log('Generating HTML for tweet media');
                    $media_html = $this->createTweetMediaHTML($tweet, $includesMedia, $retweetedMedia);
                }
                if (isset($quotedTweet->attachments->media_keys)) {
                    Debug::log('Generating HTML for quoted tweet media');
                    $quoted_media_html = $this->createTweetMediaHTML($quotedTweet, $includesMedia, $retweetedMedia);
                }
                if (isset($extURL)) {
                    Debug::log('Generating HTML for external link media');
                    if ($this->getInput('noimgscaling')) {
                        $extMediaURL = $extMediaOrig;
                    } else {
                        $extMediaURL = $extMediaScaled;
                    }
                    $ext_media_html = <<<EOD
<a href="$extURL"><img referrerpolicy="no-referrer" src="$extMediaURL" /></a>
EOD;
                }
            }

            // Generate the HTML for Item content
            $this->item['content'] = <<<EOD
<div style="float: left;">
	{$picture_html}
</div>
<div style="display: table;">
	{$cleanedTweet}
</div>
<div style="display: block; margin-top: 16px;">
	{$media_html}
EOD;

            // Add Quoted Tweet HTML, if relevant
            if (isset($quotedTweet)) {
                $quotedTweetURI = self::URI . $quotedUser->username . '/status/' . $quotedTweet->id;
                $quote_html = <<<QUOTE
<div style="display: table; border-style: solid; border-width: 1px; border-radius: 5px; padding: 5px;">
									
	<p><b>$quotedUser->name</b> @$quotedUser->username · 
	<a href="$quotedTweetURI">$quotedTweet->created_at</a></p>
	$cleanedQuotedTweet
	$quoted_media_html
</div>
QUOTE;
                $this->item['content'] .= $quote_html;
            }

            // Add External Link HTML, if relevant
            if (isset($extURL)) {
                Debug::log('Adding HTML for external link');
                $ext_html = <<<EXTERNAL
<div style="display: table; border-style: solid; border-width: 1px; border-radius: 5px; padding: 5px;">
    $ext_media_html<br>
    <a href="$extURL">$extDisplayURL</a><br>
    <b>$extTitle</b><br>
    $extDesc
</div>
EXTERNAL;
                $this->item['content'] .= $ext_html;
            }

            $this->item['content'] = htmlspecialchars_decode($this->item['content'], ENT_QUOTES);

            // Add current Item to Items array
            $this->items[] = $this->item;
        }

        // Sort all tweets in array by date
        usort($this->items, ['TwitterV2Bridge', 'compareTweetDate']);
    }

    private static function compareTweetDate($tweet1, $tweet2)
    {
        return (strtotime($tweet1['timestamp']) < strtotime($tweet2['timestamp']) ? 1 : -1);
    }

    /**
     * Tries to make an API call to Twitter.
     * @param $api string API entry point
     * @param $params array additional URI parmaeters
     * @return object json data
     */
    private function makeApiCall($api, $authHeaders, $params)
    {
        $uri = self::API_URI . $api . '?' . http_build_query($params);
        $result = getContents($uri, $authHeaders, [], false);
        $data = json_decode($result);
        return $data;
    }

    /**
     * Change format of URLs in tweet text
     * @param $tweetObject object current Tweet JSON
     * @param $tweetText string current Tweet text
     * @return string modified tweet text
     */
    private function replaceTweetURLs($tweetObject, $tweetText)
    {
        $foundUrls = false;
        // Rewrite URL links, based on URL list in tweet object
        if (isset($tweetObject->entities->urls)) {
            foreach ($tweetObject->entities->urls as $url) {
                $tweetText = str_replace(
                    $url->url,
                    '<a href="' . $url->expanded_url
                    . '">' . $url->display_url . '</a>',
                    $tweetText
                );
            }
            $foundUrls = true;
        }
        // Regex fallback for rewriting URL links. Should never trigger?
        if ($foundUrls === false) {
            $reg_ex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
            if (preg_match($reg_ex, $tweetText, $url)) {
                $tweetText = preg_replace(
                    $reg_ex,
                    "<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ",
                    $tweetText
                );
            }
        }
        // Fix back-to-back URLs by adding a <br>
        $reg_ex = '/\/a>\s*<a/';
        $tweetText = preg_replace($reg_ex, '/a><br><a', $tweetText);

        return $tweetText;
    }

    /**
     * Find User object for Retweeted/Quoted tweet
     * @param $tweetObject object current Tweet JSON
     * @param $retweetedUsers
     * @param $includesUsers
     * @return object found User
     */
    private function getTweetUser($tweetObject, $retweetedUsers, $includesUsers)
    {
        $originalUser = new stdClass(); // make the linters stop complaining
        if (isset($retweetedUsers)) {
            Debug::log('Searching for tweet author_id in $retweetedUsers');
            foreach ($retweetedUsers as $retweetedUser) {
                if ($retweetedUser->id === $tweetObject->author_id) {
                    $matchedUser = $retweetedUser;
                    Debug::log('Found author_id match in $retweetedUsers');
                    break;
                }
            }
        }
        if (!isset($matchedUser->username) && isset($includesUsers)) {
            Debug::log('Searching for tweet author_id in $includesUsers');
            foreach ($includesUsers as $includesUser) {
                if ($includesUser->id === $tweetObject->author_id) {
                    $matchedUser = $includesUser;
                    Debug::log('Found author_id match in $includesUsers');
                    break;
                }
            }
        }
        return $matchedUser;
    }

    /**
     * Generates HTML for embedded media
     * @param $tweetObject object current Tweet JSON
     * @param $includesMedia
     * @param $retweetedMedia
     * @return string modified tweet text
     */
    private function createTweetMediaHTML($tweetObject, $includesMedia, $retweetedMedia)
    {
        $media_html = '';
        // Match media_keys in tweet to media list from, put matches into new array
        $tweetMedia = [];
        // Start by checking the original list of tweet Media includes
        if (isset($includesMedia)) {
            Debug::log('Searching for media_key in $includesMedia');
            foreach ($includesMedia as $includesMedium) {
                if (
                    in_array(
                        $includesMedium->media_key,
                        $tweetObject->attachments->media_keys
                    )
                ) {
                    Debug::log('Found media_key in $includesMedia');
                    $tweetMedia[] = $includesMedium;
                }
            }
        }
        // If no matches found, check the retweet Media includes
        if (empty($tweetMedia) && isset($retweetedMedia)) {
            Debug::log('Searching for media_key in $retweetedMedia');
            foreach ($retweetedMedia as $retweetedMedium) {
                if (
                    in_array(
                        $retweetedMedium->media_key,
                        $tweetObject->attachments->media_keys
                    )
                ) {
                    Debug::log('Found media_key in $retweetedMedia');
                    $tweetMedia[] = $retweetedMedium;
                }
            }
        }

        foreach ($tweetMedia as $media) {
            switch ($media->type) {
                case 'photo':
                    if ($this->getInput('noimgscaling')) {
                        $image = $media->url;
                        $display_image = $media->url;
                    } else {
                        $image = $media->url . '?name=orig';
                        $display_image = $media->url;
                    }
                    // add enclosures
                    $this->item['enclosures'][] = $image;

                    $media_html .= <<<EOD
<a href="{$image}">
<img
referrerpolicy="no-referrer"
src="{$display_image}" />
</a>
EOD;
                    break;
                case 'video':
                    // To Do: Is there a way to easily match this
                    // to a direct Video URL?
                    $display_image = $media->preview_image_url;

                    $media_html .= <<<EOD
<p>Video:</p><a href="{$this->item['uri']}">
<img referrerpolicy="no-referrer" src="{$display_image}" /></a>
EOD;
                    break;
                case 'animated_gif':
                    // To Do: Is there a way to easily match this to a
                    // direct animated Gif URL?
                    $display_image = $media->preview_image_url;

                    $media_html .= <<<EOD
<p>Animated Gif:</p><a href="{$this->item['uri']}">
<img referrerpolicy="no-referrer" src="{$display_image}" /></a>
EOD;
                    break;
                default:
                    Debug::log('Missing support for media type: '
                    . $media->type);
            }
        }

        return $media_html;
    }
}
