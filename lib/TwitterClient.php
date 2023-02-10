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
        $this->authorization = 'AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA';
        $this->data = $cache->loadData() ?? [];
    }

    public function fetchUserTweets(string $screenName): \stdClass
    {
        $this->fetchGuestToken();
        try {
            $userInfo = $this->fetchUserInfoByScreenName($screenName);
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                Logger::info('The guest token has expired');
                $this->data['guest_token'] = null;
                $this->fetchGuestToken();
                $userInfo = $this->fetchUserInfoByScreenName($screenName);
            } else {
                throw $e;
            }
        }

        try {
            $timeline = $this->fetchTimeline($userInfo->rest_id);
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                Logger::info('The guest token has expired');
                $this->data['guest_token'] = null;
                $this->fetchGuestToken();
                $timeline = $this->fetchTimeline($userInfo->rest_id);
            } else {
                throw $e;
            }
        }

        $result = $timeline->data->user->result;
        if ($result->__typename === 'UserUnavailable') {
            throw new \Exception('UserUnavailable');
        }
        $instructionTypes = ['TimelineAddEntries', 'TimelineClearCache'];
        $instructions = $result->timeline_v2->timeline->instructions;
        if (!isset($instructions[1])) {
            throw new \Exception('The account exists but has not tweeted yet?');
        }
        $instruction = $instructions[1];
        if ($instruction->type !== 'TimelineAddEntries') {
            throw new \Exception(sprintf('Unexpected instruction type: %s', $instruction->type));
        }
        $tweets = [];
        foreach ($instruction->entries as $entry) {
            if ($entry->content->entryType !== 'TimelineTimelineItem') {
                continue;
            }
            if (!isset($entry->content->itemContent->tweet_results->result->legacy)) {
                continue;
            }
            $tweets[] = $entry->content->itemContent->tweet_results->result->legacy;
        }
        return (object) [
            'user_info' => $userInfo,
            'tweets' => $tweets,
        ];
    }

    private function fetchGuestToken(): void
    {
        if (isset($this->data['guest_token'])) {
            Logger::info('Reusing cached guest token: ' . $this->data['guest_token']);
            return;
        }
        $url = 'https://api.twitter.com/1.1/guest/activate.json';
        $response = getContents($url, $this->createHttpHeaders(), [CURLOPT_POST => true]);
        $guest_token = json_decode($response)->guest_token;
        $this->data['guest_token'] = $guest_token;
        $this->cache->saveData($this->data);
        Logger::info("Fetch new guest token: $guest_token");
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
        $response = json_decode(getContents($url, $this->createHttpHeaders()));
        if (isset($response->errors)) {
            // Grab the first error message
            throw new \Exception(sprintf('From twitter api: "%s"', $response->errors[0]->message));
        }
        $userInfo = $response->data->user;
        $this->data[$screenName] = $userInfo;
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
        $response = json_decode(getContents($url, $this->createHttpHeaders()));
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
