<?php

class TwitterBridge extends BridgeAbstract
{
    const NAME = 'Twitter Bridge';
    const URI = 'https://twitter.com/';
    const API_URI = 'https://api.twitter.com';
    const GUEST_TOKEN_USES = 100;
    const GUEST_TOKEN_EXPIRY = 10800; // 3hrs
    const CACHE_TIMEOUT = 60 * 15; // 15min
    const DESCRIPTION = 'returns tweets';
    const MAINTAINER = 'arnd-s';
    const PARAMETERS = [
        'global' => [
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
                'title' => 'Activate to disable image scaling in tweets (keeps original image)'
            ]
        ],
        'By keyword or hashtag' => [
            'q' => [
                'name' => 'Keyword or #hashtag',
                'required' => true,
                'exampleValue' => 'rss-bridge OR rssbridge',
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
        'By username' => [
            'u' => [
                'name' => 'username',
                'required' => true,
                'exampleValue' => 'sebsauvage',
                'title' => 'Insert a user name'
            ],
            'norep' => [
                'name' => 'Without replies',
                'type' => 'checkbox',
                'title' => 'Only return initial tweets'
            ],
            'noretweet' => [
                'name' => 'Without retweets',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Hide retweets'
            ],
            'nopinned' => [
                'name' => 'Without pinned tweet',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Hide pinned tweet'
            ]
        ],
        'By list' => [
            'user' => [
                'name' => 'User',
                'required' => true,
                'exampleValue' => 'Scobleizer',
                'title' => 'Insert a user name'
            ],
            'list' => [
                'name' => 'List',
                'required' => true,
                'exampleValue' => 'Tech-News',
                'title' => 'Insert the list name'
            ],
            'filter' => [
                'name' => 'Filter',
                'exampleValue' => '#rss-bridge',
                'required' => false,
                'title' => 'Specify term to search for'
            ]
        ],
        'By list ID' => [
            'listid' => [
                'name' => 'List ID',
                'exampleValue' => '31748',
                'required' => true,
                'title' => 'Insert the list id'
            ],
            'filter' => [
                'name' => 'Filter',
                'exampleValue' => '#rss-bridge',
                'required' => false,
                'title' => 'Specify term to search for'
            ]
        ]
    ];

    private $apiKey     = null;
    private $guestToken = null;
    private $authHeaders = [];
    private ?string $feedIconUrl = null;

    public function detectParameters($url)
    {
        $params = [];

        // By keyword or hashtag (search)
        $regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/search.*(\?|&)q=([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By keyword or hashtag';
            $params['q'] = urldecode($matches[4]);
            return $params;
        }

        // By hashtag
        $regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/hashtag\/([^\/?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By keyword or hashtag';
            $params['q'] = urldecode($matches[3]);
            return $params;
        }

        // By list
        $regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)\/lists\/([^\/?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By list';
            $params['user'] = urldecode($matches[3]);
            $params['list'] = urldecode($matches[4]);
            return $params;
        }

        // By username
        $regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By username';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }

    public function getName()
    {
        switch ($this->queriedContext) {
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
            default:
                return parent::getName();
        }
        return 'Twitter ' . $specific . $this->getInput($param);
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
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
            default:
                return parent::getURI();
        }
    }

    private function getFullText($id)
    {
        $url = sprintf(
            'https://cdn.syndication.twimg.com/tweet-result?id=%s&lang=en&token=449yf2pc4g',
            $id
        );

        return json_decode(getContents($url), false);
    }

    public function collectData()
    {
        // $data will contain an array of all found tweets (unfiltered)
        $data = null;
        // Contains user data (when in by username context)
        $user = null;
        // Array of all found tweets
        $tweets = [];

        // Get authentication information
        $api = new TwitterClient($this->cache);
        // Try to get all tweets
        switch ($this->queriedContext) {
            case 'By username':
                $screenName = $this->getInput('u');
                $screenName = trim($screenName);
                $screenName = ltrim($screenName, '@');

                $data = $api->fetchUserTweets($screenName);

                break;

            case 'By keyword or hashtag':
                // Does not work with the recent twitter changes
                $params = [
                    'q'                 => urlencode($this->getInput('q')),
                    'tweet_mode'        => 'extended',
                    'tweet_search_mode' => 'live',
                ];

                $tweets = $api->search($params)->statuses;
                $data = (object) [
                    'tweets' => $tweets
                ];
                break;

            case 'By list':
                // Does not work with the recent twitter changes
                // $params = [
                // 'slug'              => strtolower($this->getInput('list')),
                // 'owner_screen_name' => strtolower($this->getInput('user')),
                // 'tweet_mode'        => 'extended',
                // ];
                $query = [
                    'screenName' => strtolower($this->getInput('user')),
                    'listSlug' => strtolower($this->getInput('list'))
                ];

                $data = $api->fetchListTweets($query, $this->queriedContext);
                break;

            case 'By list ID':
                // Does not work with the recent twitter changes
                // $params = [
                // 'list_id'           => $this->getInput('listid'),
                // 'tweet_mode'        => 'extended',
                // ];

                $query = [
                    'listId' => $this->getInput('listid')
                ];

                $data = $api->fetchListTweets($query, $this->queriedContext);
                break;
            default:
                returnServerError('Invalid query context !');
        }

        if (!$data) {
            switch ($this->queriedContext) {
                case 'By keyword or hashtag':
                    returnServerError('twitter: No results for this query.');
                    // fall-through
                case 'By username':
                    returnServerError('Requested username can\'t be found.');
                    // fall-through
                case 'By list':
                    returnServerError('Requested username or list can\'t be found');
            }
        }

        $hidePictures = $this->getInput('nopic');

        $hidePinned = $this->getInput('nopinned');
        if ($hidePinned) {
            $pinnedTweetId = null;
            if ($data->user_info && $data->user_info->legacy->pinned_tweet_ids_str) {
                $pinnedTweetId = $data->user_info->legacy->pinned_tweet_ids_str[0];
            }
        }

        // Array of Tweet IDs
        $tweetIds = [];
        // Filter out unwanted tweets
        foreach ($data->tweets as $tweet) {
            if (!$tweet) {
                continue;
            }

            if (isset($tweet->legacy)) {
                $legacy_info = $tweet->legacy;
            } else {
                $legacy_info = $tweet;
            }

            // Filter out retweets to remove possible duplicates of original tweet
            switch ($this->queriedContext) {
                case 'By keyword or hashtag':
		    // phpcs:ignore
                    if ((isset($legacy_info->retweeted_status) || isset($legacy_info->retweeted_status_result)) && substr($legacy_info->full_text, 0, 4) === 'RT @') {
                        continue 2;
                    }
                    break;
            }

            // Skip own Retweets...
            if (isset($legacy_info->retweeted_status) && $legacy_info->retweeted_status->user->id_str === $tweet->user->id_str) {
                continue;
            // phpcs:ignore
            } elseif (isset($legacy_info->retweeted_status_result) && $tweet->retweeted_status_result->result->legacy->user_id_str === $legacy_info->user_id_str) {
                continue;
            }

            $tweetId = (isset($legacy_info->id_str) ? $legacy_info->id_str : $tweet->rest_id);
            // Skip pinned tweet
            if ($hidePinned && ($tweetId === $pinnedTweetId)) {
                continue;
            }

            if (isset($tweet->rest_id)) {
                $tweetIds[] = $tweetId;
            }
            $rtweet = $legacy_info;
            $tweets[] = $rtweet;
        }

        if ($this->queriedContext === 'By username') {
            $this->feedIconUrl = $data->user_info->legacy->profile_image_url_https ?? null;
        }

        $i = 0;
        foreach ($tweets as $tweet) {
            $item = [];

            $realtweet = $tweet;
            $tweetId = (isset($tweetIds[$i]) ? $tweetIds[$i] : $realtweet->conversation_id_str);
            if (isset($tweet->retweeted_status)) {
                // Tweet is a Retweet, so set author based on original tweet and set realtweet for reference to the right content
                $realtweet = $tweet->retweeted_status;
            } elseif (isset($tweet->retweeted_status_result)) {
                $tweetId = $tweet->retweeted_status_result->result->rest_id;
                $realtweet = $tweet->retweeted_status_result->result->legacy;
            }

            if (isset($realtweet->truncated) && $realtweet->truncated) {
                try {
                    $realtweet = $this->getFullText($realtweet->id_str);
                } catch (HttpException $e) {
                    $realtweet = $tweet;
                }
            }

            if (!$realtweet) {
                $realtweet = $tweet;
            }

            switch ($this->queriedContext) {
                case 'By username':
                    if ($this->getInput('norep') && isset($tweet->in_reply_to_status_id)) {
                        continue 2;
                    }
                    $item['username']  = $data->user_info->legacy->screen_name;
                    $item['fullname']  = $data->user_info->legacy->name;
                    $item['avatar']    = $data->user_info->legacy->profile_image_url_https;
                    $item['id']        = (isset($realtweet->id_str) ? $realtweet->id_str : $tweetId);
                    break;
                case 'By list':
                case 'By list ID':
                    $item['username']  = $data->userIds[$i]->legacy->screen_name;
                    $item['fullname']  = $data->userIds[$i]->legacy->name;
                    $item['avatar']    = $data->userIds[$i]->legacy->profile_image_url_https;
                    $item['id']        = $realtweet->conversation_id_str;
                    break;
                case 'By keyword or hashtag':
                    $item['username']  = $realtweet->user->screen_name;
                    $item['fullname']  = $realtweet->user->name;
                    $item['avatar']    = $realtweet->user->profile_image_url_https;
                    $item['id']        = $realtweet->id_str;
                    break;
            }

            $item['timestamp'] = $realtweet->created_at;
            $item['uri']       = self::URI . $item['username'] . '/status/' . $item['id'];
            $item['author']    = ((isset($tweet->retweeted_status) || (isset($tweet->retweeted_status_result))) ? 'RT: ' : '')
                         . $item['fullname']
                         . ' (@'
                         . $item['username'] . ')';

            // Convert plain text URLs into HTML hyperlinks
            if (isset($realtweet->full_text)) {
                $fulltext = $realtweet->full_text;
            } else {
                $fulltext = $realtweet->text;
            }
            $cleanedTweet = $fulltext;

            $foundUrls = false;

            if (substr($cleanedTweet, 0, 4) === 'RT @') {
                $cleanedTweet = substr($cleanedTweet, 3);
            }

            if (isset($realtweet->entities->media)) {
                foreach ($realtweet->entities->media as $media) {
                    $cleanedTweet = str_replace(
                        $media->url,
                        '<a href="' . $media->expanded_url . '">' . $media->display_url . '</a>',
                        $cleanedTweet
                    );
                    $foundUrls = true;
                }
            }
            if (isset($realtweet->entities->urls)) {
                foreach ($realtweet->entities->urls as $url) {
                    $cleanedTweet = str_replace(
                        $url->url,
                        '<a href="' . $url->expanded_url . '">' . $url->display_url . '</a>',
                        $cleanedTweet
                    );
                    $foundUrls = true;
                }
            }
            if ($foundUrls === false) {
                // fallback to regex'es
                $reg_ex = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
                if (preg_match($reg_ex, $fulltext, $url)) {
                    $cleanedTweet = preg_replace(
                        $reg_ex,
                        "<a href='{$url[0]}' target='_blank'>{$url[0]}</a> ",
                        $cleanedTweet
                    );
                }
            }
            // generate the title
            $item['title'] = strip_tags($cleanedTweet);

            // Add avatar
            $picture_html = '';
            if (!$hidePictures) {
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

            $medias = [];
            if (isset($realtweet->extended_entities->media)) {
                $medias = $realtweet->extended_entities->media;
            } else if (isset($realtweet->mediaDetails)) {
                $medias = $realtweet->mediaDetails;
            }

            // Get images
            $media_html = '';
            if (!$this->getInput('noimg')) {
                foreach ($medias as $media) {
                    switch ($media->type) {
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
                            if (isset($media->video_info)) {
                                $link = $media->expanded_url;
                                $poster = $media->media_url_https;
                                $video = null;
                                $maxBitrate = -1;
                                foreach ($media->video_info->variants as $variant) {
                                    $bitRate = $variant->bitrate ?? -100;
                                    if ($bitRate > $maxBitrate) {
                                        $maxBitrate = $bitRate;
                                        $video = $variant->url;
                                    }
                                }
                                if (!is_null($video)) {
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

            switch ($this->queriedContext) {
                case 'By list':
                case 'By list ID':
                    // Check if filter applies to list (using raw content)
                    if ($this->getInput('filter')) {
                        if (stripos($cleanedTweet, $this->getInput('filter')) === false) {
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

            // put out
            $i++;
            $this->items[] = $item;
        }

        usort($this->items, ['TwitterBridge', 'compareTweetId']);
    }

    public function getIcon()
    {
        return $this->feedIconUrl ?? parent::getIcon();
    }

    private static function compareTweetId($tweet1, $tweet2)
    {
        return (intval($tweet1['id']) < intval($tweet2['id']) ? 1 : -1);
    }
}
