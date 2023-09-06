<?php

declare(strict_types=1);

class TwitterClient
{
    private CacheInterface $cache;
    private string $authorization;
    private $data;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;

        $cache->setScope('twitter');
        $cache->setKey(['cache']);
        $cache->purgeCache(60 * 60 * 3);

        $this->data = $this->cache->loadData() ?? [];
        $this->authorization = 'AAAAAAAAAAAAAAAAAAAAAGHtAgAAAAAA%2Bx7ILXNILCqkSGIzy6faIHZ9s3Q%3DQy97w6SIrzE7lQwPJEYQBsArEE2fC25caFwRBvAGi456G09vGR';
        $this->tw_consumer_key = '3nVuSoBZnx6U4vzUxf5w';
        $this->tw_consumer_secret = 'Bcs59EFbbsdF6Sl9Ng71smgStWEGwXXKSjYvPVt7qys';
        $this->oauth_token = ''; //Fill here
        $this->oauth_token_secret = ''; //Fill here
    }

    private function getOauthAuthorization(
        $oauth_token,
        $oauth_token_secret,
        $method = 'GET',
        $url = '',
        $body = '',
        $timestamp = null,
        $oauth_nonce = null
    ) {
        if (!$url) {
            return '';
        }
        $method = strtoupper($method);
        $parseUrl = parse_url($url);
        $link = $parseUrl['scheme'] . '://' . $parseUrl['host'] . $parseUrl['path'];
        parse_str($parseUrl['query'], $query_params);
        if ($body) {
            parse_str($body, $body_params);
            $query_params = array_merge($query_params, $body_params);
        }
        $payload = [
            'oauth_version' => '1.0',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_consumer_key' => $this->tw_consumer_key,
            'oauth_token' => $oauth_token,
            'oauth_nonce' => $oauth_nonce ? $oauth_nonce : implode('', array_fill(0, 3, strval(time()))),
            'oauth_timestamp' => $timestamp ? $timestamp : time(),
        ];
        $payload = array_merge($payload, $query_params);
        ksort($payload);

        $url_parts = parse_url($url);
        $url_parts['query'] = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $base_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
        $signature_base_string = strtoupper($method) . '&' . rawurlencode($base_url) . '&' . rawurlencode($url_parts['query']);
        $hmac_key = $this->tw_consumer_secret . '&' . $oauth_token_secret;
        $hex_signature = hash_hmac('sha1', $signature_base_string, $hmac_key, true);
        $signature = base64_encode($hex_signature);

        $header_params = [
            'oauth_version' => '1.0',
            'oauth_token' => $oauth_token,
            'oauth_nonce' => $payload['oauth_nonce'],
            'oauth_timestamp' => $payload['oauth_timestamp'],
            'oauth_signature' => $signature,
            'oauth_consumer_key' => $this->tw_consumer_key,
            'oauth_signature_method' => 'HMAC-SHA1',
        ];
        // ksort($header_params);
        $header_values = [];
        foreach ($header_params as $key => $value) {
            $header_values[] = rawurlencode($key) . '="' . (is_int($value) ? $value : rawurlencode($value)) . '"';
        }
        return 'OAuth realm="http://api.twitter.com/", ' . implode(', ', $header_values);
    }

    private function extractTweetAndUsersFromGraphQL($timeline)
    {
        if (isset($timeline->data->user)) {
            $result = $timeline->data->user->result;
            $instructions = $result->timeline_v2->timeline->instructions;
        } elseif (isset($timeline->data->user_result)) {
            $result = $timeline->data->user_result->result->timeline_response;
            $instructions = $result->timeline->instructions;
        }

        if (isset($result->__typename) && $result->__typename === 'UserUnavailable') {
            throw new \Exception('UserUnavailable');
        }

        if (isset($timeline->data->list)) {
            $result = $timeline->data->list->timeline_response;
            $instructions = $result->timeline->instructions;
        }

        if (!isset($result) && !isset($instructions)) {
            throw new \Exception('Unable to fetch user/list timeline');
        }

        $instructionTypes = [
            'TimelineAddEntries',
            'TimelineClearCache',
            'TimelinePinEntry', // unclear purpose, maybe pinned tweet?
        ];
        if (!isset($instructions[1]) && isset($timeline->data->user)) {
            throw new \Exception('The account exists but has not tweeted yet?');
        }

        $entries = null;
        foreach ($instructions as $instruction) {
            $instructionType = '';
            if (isset($instruction->type)) {
                $instructionType = $instruction->type;
            } else {
                $instructionType = $instruction->__typename;
            }

            if ($instructionType === 'TimelineAddEntries') {
                $entries = $instruction->entries;
                break;
            }
        }
        if (!$entries) {
            throw new \Exception(sprintf('Unable to find time line tweets in: %s', implode(',', array_column($instructions, 'type'))));
        }

        $tweets = [];
        $userIds = [];
        foreach ($entries as $entry) {
            $entryType = '';

            if (isset($entry->content->entryType)) {
                $entryType = $entry->content->entryType;
            } else {
                $entryType = $entry->content->__typename;
            }

            if ($entryType !== 'TimelineTimelineItem') {
                continue;
            }

            if (isset($timeline->data->user)) {
                if (!isset($entry->content->itemContent->tweet_results->result)) {
                    continue;
                }

                if (isset($entry->content->itemContent->promotedMetadata)) {
                    continue;
                }

                $tweets[] = $entry->content->itemContent->tweet_results->result;

                $userIds[] = $entry->content->itemContent->tweet_results->result->core->user_results->result;
            } else {
                if (!isset($entry->content->content->tweetResult->result->legacy)) {
                    continue;
                }

                // Filter out any advertise tweet
                if (isset($entry->content->content->tweetPromotedMetadata)) {
                    continue;
                }

                $tweets[] = $entry->content->content->tweetResult->result;

                $userIds[] = $entry->content->content->tweetResult->result->core->user_result->result;
            }
        }

        return (object) [
            'userIds' => $userIds,
            'tweets' => $tweets,
        ];
    }

    private function extractTweetFromSearch($searchResult)
    {
        return $searchResult->statuses;
    }

    public function fetchUserTweets(string $screenName): \stdClass
    {
        $this->fetchGuestToken();
        try {
            $userInfo = $this->fetchUserInfoByScreenName($screenName);
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                $this->data['guest_token'] = null;
                $this->fetchGuestToken();
                $userInfo = $this->fetchUserInfoByScreenName($screenName);
            } else {
                throw $e;
            }
        }

        $timeline = $this->fetchTimeline($userInfo->rest_id);
        // try {
        //     // $timeline = $this->fetchTimelineUsingSearch($screenName);
        // } catch (HttpException $e) {
        //     if ($e->getCode() === 403) {
        //         $this->data['guest_token'] = null;
        //         $this->fetchGuestToken();
        //         // $timeline = $this->fetchTimelineUsingSearch($screenName);
        //         $timeline = $this->fetchTimeline($userInfo->rest_id);
        //     } else {
        //         throw $e;
        //     }
        // }

        // $tweets = $this->extractTweetFromSearch($timeline);
        $tweets = $this->extractTweetAndUsersFromGraphQL($timeline)->tweets;

        return (object) [
            'user_info' => $userInfo,
            'tweets' => $tweets,
        ];
    }

    public function fetchListTweets($query, $operation = '')
    {
        $id = '';
        $this->fetchGuestToken();
        if ($operation == 'By list') {
            try {
                $listInfo = $this->fetchListInfoBySlug($query['screenName'], $query['listSlug']);
                $id = $listInfo->id_str;
            } catch (HttpException $e) {
                if ($e->getCode() === 403) {
                    $this->data['guest_token'] = null;
                    $this->fetchGuestToken();
                    $listInfo = $this->fetchListInfoBySlug($query['screenName'], $query['listSlug']);
                    $id = $listInfo->id_str;
                } else {
                    throw $e;
                }
            }
        } else if ($operation == 'By list ID') {
            $id = $query['listId'];
        } else {
            throw new \Exception('Unknown operation to make list tweets');
        }

        try {
            $timeline = $this->fetchListTimeline($id);
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                $this->data['guest_token'] = null;
                $this->fetchGuestToken();
                $timeline = $this->fetchListTimeline($id);
            } else {
                throw $e;
            }
        }

        $data = $this->extractTweetAndUsersFromGraphQL($timeline);

        return $data;
    }

    private function fetchGuestToken(): void
    {
        if (isset($this->data['guest_token'])) {
            return;
        }
        $url = 'https://api.twitter.com/1.1/guest/activate.json';
        $response = getContents($url, $this->createHttpHeaders(), [CURLOPT_POST => true]);
        $guest_token = json_decode($response)->guest_token;
        $this->data['guest_token'] = $guest_token;

        $this->cache->setScope('twitter');
        $this->cache->setKey(['cache']);
        $this->cache->saveData($this->data);
    }

    private function fetchUserInfoByScreenName(string $screenName)
    {
        if (isset($this->data[$screenName])) {
            return $this->data[$screenName];
        }
        $variables = [
            'screen_name' => $screenName,
            'withHighlightedLabel' => true
        ];
        $url = sprintf(
            'https://twitter.com/i/api/graphql/hc-pka9A7gyS3xODIafnrQ/UserByScreenName?variables=%s',
            urlencode(json_encode($variables))
        );
        $response = Json::decode(getContents($url, $this->createHttpHeaders()), false);
        if (isset($response->errors)) {
            // Grab the first error message
            throw new \Exception(sprintf('From twitter api: "%s"', $response->errors[0]->message));
        }
        $userInfo = $response->data->user;
        $this->data[$screenName] = $userInfo;

        $this->cache->setScope('twitter');
        $this->cache->setKey(['cache']);
        $this->cache->saveData($this->data);
        return $userInfo;
    }

    private function fetchTimeline($userId)
    {
        $variables = [
            'autoplay_enabled' => true,
            'count' => 40,
            'includeEditControl' => true,
            'includeEditPerspective' => false,
            'includeHasBirdwatchNotes' => false,
            'includeTweetImpression' => true,
            'includeTweetVisibilityNudge' => true,
            'rest_id' => $userId
        ];
        $features = [
            'android_graphql_skip_api_media_color_palette' => true,
            'blue_business_profile_image_shape_enabled' => true,
            'creator_subscriptions_subscription_count_enabled' => true,
            'creator_subscriptions_tweet_preview_api_enabled' => true,
            'freedom_of_speech_not_reach_fetch_enabled' => true,
            'longform_notetweets_consumption_enabled' => true,
            'longform_notetweets_inline_media_enabled' => true,
            'longform_notetweets_rich_text_read_enabled' => true,
            'subscriptions_verification_info_enabled' => true,
            'super_follow_badge_privacy_enabled' => true,
            'super_follow_exclusive_tweet_notifications_enabled' => true,
            'super_follow_tweet_api_enabled' => true,
            'super_follow_user_api_enabled' => true,
            'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => true,
            'tweetypie_unmention_optimization_enabled' => true,
            'unified_cards_ad_metadata_container_dynamic_card_content_query_enabled' => true,
        ];
        $url = sprintf(
            'https://api.twitter.com/graphql/3JNH4e9dq1BifLxAa3UMWg/UserWithProfileTweetsQueryV2?variables=%s&features=%s',
            urlencode(json_encode($variables)),
            urlencode(json_encode($features))
        );
        $oauth = $this->getOauthAuthorization($this->oauth_token, $this->oauth_token_secret, 'GET', $url);
        $response = Json::decode(getContents($url, $this->createHttpHeaders($oauth)), false);
        return $response;
    }

    private function fetchTimelineUsingSearch($screenName)
    {
        $params = [
            'q' => 'from:' . $screenName,
            'modules' => 'status',
            'result_type' => 'recent'
        ];
        $response = $this->search($params);
        return $response;
    }

    public function search($queryParam)
    {
         $url = sprintf(
             'https://api.twitter.com/1.1/search/tweets.json?%s',
             http_build_query($queryParam)
         );
        $oauth = $this->getOauthAuthorization($this->oauth_token, $this->oauth_token_secret, 'GET', $url);
        $response = Json::decode(getContents($url, $this->createHttpHeaders($oauth)), false);
        return $response;
    }

    private function fetchListInfoBySlug($screenName, $listSlug)
    {
        if (isset($this->data[$screenName . '-' . $listSlug])) {
            return $this->data[$screenName . '-' . $listSlug];
        }

        $features = [
            'android_graphql_skip_api_media_color_palette' => false,
            'blue_business_profile_image_shape_enabled' => false,
            'creator_subscriptions_subscription_count_enabled' => false,
            'creator_subscriptions_tweet_preview_api_enabled' => true,
            'freedom_of_speech_not_reach_fetch_enabled' => false,
            'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => false,
            'hidden_profile_likes_enabled' => false,
            'highlights_tweets_tab_ui_enabled' => false,
            'interactive_text_enabled' => false,
            'longform_notetweets_consumption_enabled' => true,
            'longform_notetweets_inline_media_enabled' => false,
            'longform_notetweets_richtext_consumption_enabled' => true,
            'longform_notetweets_rich_text_read_enabled' => false,
            'responsive_web_edit_tweet_api_enabled' => false,
            'responsive_web_enhance_cards_enabled' => false,
            'responsive_web_graphql_exclude_directive_enabled' => true,
            'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
            'responsive_web_graphql_timeline_navigation_enabled' => false,
            'responsive_web_media_download_video_enabled' => false,
            'responsive_web_text_conversations_enabled' => false,
            'responsive_web_twitter_article_tweet_consumption_enabled' => false,
            'responsive_web_twitter_blue_verified_badge_is_enabled' => true,
            'rweb_lists_timeline_redesign_enabled' => true,
            'spaces_2022_h2_clipping' => true,
            'spaces_2022_h2_spaces_communities' => true,
            'standardized_nudges_misinfo' => false,
            'subscriptions_verification_info_enabled' => true,
            'subscriptions_verification_info_reason_enabled' => true,
            'subscriptions_verification_info_verified_since_enabled' => true,
            'super_follow_badge_privacy_enabled' => false,
            'super_follow_exclusive_tweet_notifications_enabled' => false,
            'super_follow_tweet_api_enabled' => false,
            'super_follow_user_api_enabled' => false,
            'tweet_awards_web_tipping_enabled' => false,
            'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => false,
            'tweetypie_unmention_optimization_enabled' => false,
            'unified_cards_ad_metadata_container_dynamic_card_content_query_enabled' => false,
            'verified_phone_label_enabled' => false,
            'vibe_api_enabled' => false,
            'view_counts_everywhere_api_enabled' => false
        ];
        $variables = [
            'screenName' => $screenName,
            'listSlug' => $listSlug
        ];

        $url = sprintf(
            'https://twitter.com/i/api/graphql/-kmqNvm5Y-cVrfvBy6docg/ListBySlug?variables=%s&features=%s',
            urlencode(json_encode($variables)),
            urlencode(json_encode($features))
        );

        $response = Json::decode(getContents($url, $this->createHttpHeaders()), false);
        if (isset($response->errors)) {
            // Grab the first error message
            throw new \Exception(sprintf('From twitter api: "%s"', $response->errors[0]->message));
        }
        $listInfo = $response->data->user_by_screen_name->list;
        $this->data[$screenName . '-' . $listSlug] = $listInfo;

        $this->cache->setScope('twitter');
        $this->cache->setKey(['cache']);
        $this->cache->saveData($this->data);
        return $listInfo;
    }

    private function fetchListTimeline($listId)
    {
        $features = [
            'android_graphql_skip_api_media_color_palette' => false,
            'blue_business_profile_image_shape_enabled' => false,
            'creator_subscriptions_subscription_count_enabled' => false,
            'creator_subscriptions_tweet_preview_api_enabled' => true,
            'freedom_of_speech_not_reach_fetch_enabled' => false,
            'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => false,
            'hidden_profile_likes_enabled' => false,
            'highlights_tweets_tab_ui_enabled' => false,
            'interactive_text_enabled' => false,
            'longform_notetweets_consumption_enabled' => true,
            'longform_notetweets_inline_media_enabled' => false,
            'longform_notetweets_richtext_consumption_enabled' => true,
            'longform_notetweets_rich_text_read_enabled' => false,
            'responsive_web_edit_tweet_api_enabled' => false,
            'responsive_web_enhance_cards_enabled' => false,
            'responsive_web_graphql_exclude_directive_enabled' => true,
            'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
            'responsive_web_graphql_timeline_navigation_enabled' => false,
            'responsive_web_media_download_video_enabled' => false,
            'responsive_web_text_conversations_enabled' => false,
            'responsive_web_twitter_article_tweet_consumption_enabled' => false,
            'responsive_web_twitter_blue_verified_badge_is_enabled' => true,
            'rweb_lists_timeline_redesign_enabled' => true,
            'spaces_2022_h2_clipping' => true,
            'spaces_2022_h2_spaces_communities' => true,
            'standardized_nudges_misinfo' => false,
            'subscriptions_verification_info_enabled' => true,
            'subscriptions_verification_info_reason_enabled' => true,
            'subscriptions_verification_info_verified_since_enabled' => true,
            'super_follow_badge_privacy_enabled' => false,
            'super_follow_exclusive_tweet_notifications_enabled' => false,
            'super_follow_tweet_api_enabled' => false,
            'super_follow_user_api_enabled' => false,
            'tweet_awards_web_tipping_enabled' => false,
            'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => false,
            'tweetypie_unmention_optimization_enabled' => false,
            'unified_cards_ad_metadata_container_dynamic_card_content_query_enabled' => false,
            'verified_phone_label_enabled' => false,
            'vibe_api_enabled' => false,
            'view_counts_everywhere_api_enabled' => false
        ];
        $variables = [
            'rest_id' => $listId,
            'count' => 20
        ];

        $url = sprintf(
            'https://api.twitter.com/graphql/BbGLL1ZfMibdFNWlk7a0Pw/ListTimeline?variables=%s&features=%s',
            urlencode(json_encode($variables)),
            urlencode(json_encode($features))
        );
        $oauth = $this->getOauthAuthorization($this->oauth_token, $this->oauth_token_secret, 'GET', $url);
        $response = Json::decode(getContents($url, $this->createHttpHeaders($oauth)), false);
        return $response;
    }

    private function createHttpHeaders($oauth = null): array
    {
        $headers = [
            'authorization' => sprintf('Bearer %s', $this->authorization),
            'x-guest-token' => $this->data['guest_token'] ?? null,
        ];
        if (isset($oauth)) {
            $headers['authorization'] = $oauth;
            unset($headers['x-guest-token']);
        }
        foreach ($headers as $key => $value) {
            $headers2[] = sprintf('%s: %s', $key, $value);
        }
        return $headers2;
    }
}
