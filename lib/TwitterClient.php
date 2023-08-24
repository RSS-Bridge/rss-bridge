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
    }

    private function extractTweetAndUsersFromGraphQL($timeline)
    {
        if (isset($timeline->data->user)) {
            $result = $timeline->data->user->result;
            $instructions = $result->timeline_v2->timeline->instructions;
        } else {
            $result = $timeline->data->list->timeline_response;
            $instructions = $result->timeline->instructions;
        }
        if (isset($result->__typename) && $result->__typename === 'UserUnavailable') {
            throw new \Exception('UserUnavailable');
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
                if (!isset($entry->content->itemContent->tweet_results->result->legacy)) {
                    continue;
                }
                $tweets[] = $entry->content->itemContent->tweet_results->result->legacy;

                $userIds[] = $entry->content->itemContent->tweet_results->result->core->user_results->result;
            } else {
                if (!isset($entry->content->content->tweetResult->result->legacy)) {
                    continue;
                }
                $tweets[] = $entry->content->content->tweetResult->result->legacy;

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

        try {
            $timeline = $this->fetchTimelineUsingSearch($screenName);
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                $this->data['guest_token'] = null;
                $this->fetchGuestToken();
                $timeline = $this->fetchTimelineUsingSearch($screenName);
            } else {
                throw $e;
            }
        }

        $tweets = $this->extractTweetFromSearch($timeline);

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
        } elseif ($operation === 'By list ID') {
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
            'userId' => $userId,
            'count' => 40,
            'includePromotedContent' => true,
            'withQuickPromoteEligibilityTweetFields' => true,
            'withSuperFollowsUserFields' => true,
            'withDownvotePerspective' => false,
            'withReactionsMetadata' => false,
            'withReactionsPerspective' => false,
            'withSuperFollowsTweetFields' => true,
            'withVoice' => true,
            'withV2Timeline' => true,
        ];
        $features = [
            'responsive_web_twitter_blue_verified_badge_is_enabled' => true,
            'responsive_web_graphql_exclude_directive_enabled' => false,
            'verified_phone_label_enabled' => false,
            'responsive_web_graphql_timeline_navigation_enabled' => true,
            'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
            'longform_notetweets_consumption_enabled' => true,
            'tweetypie_unmention_optimization_enabled' => true,
            'vibe_api_enabled' => true,
            'responsive_web_edit_tweet_api_enabled' => true,
            'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => true,
            'view_counts_everywhere_api_enabled' => true,
            'freedom_of_speech_not_reach_appeal_label_enabled' => false,
            'standardized_nudges_misinfo' => true,
            'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => false,
            'interactive_text_enabled' => true,
            'responsive_web_text_conversations_enabled' => false,
            'responsive_web_enhance_cards_enabled' => false,
        ];
        $url = sprintf(
            'https://twitter.com/i/api/graphql/WZT7sCTrLvSOaWOXLDsWbQ/UserTweets?variables=%s&features=%s',
            urlencode(json_encode($variables)),
            urlencode(json_encode($features))
        );
        $response = Json::decode(getContents($url, $this->createHttpHeaders()), false);
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
        $response = Json::decode(getContents($url, $this->createHttpHeaders()), false);
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
        if (!isset($response->data->user_by_screen_name->list)) {
            throw new \Exception(
                sprintf('Unable to find list in twitter response for %s, %s', $screenName, $listSlug)
            );
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
            'https://twitter.com/i/api/graphql/BbGLL1ZfMibdFNWlk7a0Pw/ListTimeline?variables=%s&features=%s',
            urlencode(json_encode($variables)),
            urlencode(json_encode($features))
        );
        $response = Json::decode(getContents($url, $this->createHttpHeaders()), false);
        return $response;
    }

    private function createHttpHeaders(): array
    {
        $headers = [
            'authorization' => sprintf('Bearer %s', $this->authorization),
            'x-guest-token' => $this->data['guest_token'] ?? null,
        ];
        foreach ($headers as $key => $value) {
            $headers[] = sprintf('%s: %s', $key, $value);
        }
        return $headers;
    }
}
