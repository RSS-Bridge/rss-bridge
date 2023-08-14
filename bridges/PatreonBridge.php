<?php

class PatreonBridge extends BridgeAbstract
{
    const NAME = 'Patreon Bridge';
    const URI = 'https://www.patreon.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns posts by creators on Patreon';
    const MAINTAINER = 'Roliga, mruac';
    const PARAMETERS = [[
        'creator' => [
            'name' => 'Creator',
            'type' => 'text',
            'required' => true,
			'exampleValue' => 'user?u=13425451',
            'title' => 'Creator name as seen in their page URL'
        ]
    ]];

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOMCached($url);
        $regex = '#/api/campaigns/([0-9]+)#';
        if (preg_match($regex, $html->save(), $matches) > 0) {
            $campaign_id = $matches[1];
        } else {
            returnServerError('Could not find campaign ID');
        }

        $query = [
            'include' => implode(',', [
                'user',
                'attachments',
                'user_defined_tags',
                //'campaign',
                //'poll.choices',
                //'poll.current_user_responses.user',
                //'poll.current_user_responses.choice',
                //'poll.current_user_responses.poll',
                //'access_rules.tier.null',
                //'images.null',
                //'audio.null'
            ]),
            'fields' => [
                'post' => implode(',', [
                    //'change_visibility_at',
                    //'comment_count',
                    'content',
                    //'current_user_can_delete',
                    //'current_user_can_view',
                    //'current_user_has_liked',
                    //'embed',
                    'image',
                    //'is_paid',
                    //'like_count',
                    //'min_cents_pledged_to_view',
                    //'patreon_url',
                    //'patron_count',
                    //'pledge_url',
                    //'post_file',
                    //'post_metadata',
                    //'post_type',
                    'published_at',
                    'teaser_text',
                    //'thumbnail_url',
                    'title',
                    //'upgrade_url',
                    'url',
                    //'was_posted_by_campaign_owner'
                ]),
                'user' => implode(',', [
                    //'image_url',
                    'full_name',
                    //'url'
                ])
            ],
            'filter' => [
                'contains_exclusive_posts' => true,
                'is_draft' => false,
                'campaign_id' => $campaign_id
            ],
            'sort' => '-published_at'
        ];
        $posts = $this->apiGet('posts', $query);

        foreach ($posts->data as $post) {
            $item = [
                'uri' => $post->attributes->url,
                'title' => $post->attributes->title,
                'timestamp' => $post->attributes->published_at,
                'content' => '',
                'uid' => 'patreon.com/' . $post->id
            ];

            $user = $this->findInclude(
                $posts,
                'user',
                $post->relationships->user->data->id
            );
            $item['author'] = $user->full_name;

            $image = $post->attributes->image ?? null;
            if ($image) {
                $logo = sprintf(
                    '<p><a href="%s"><img src="%s" /></a></p>',
                    $post->attributes->url,
                    $image->thumb_url ?? $image->url ?? $this->getURI()
                );
                $item['content'] .= $logo;
            }

            if (isset($post->attributes->content)) {
                $item['content'] .= $post->attributes->content;
            } elseif (isset($post->attributes->teaser_text)) {
                $item['content'] .= '<p>'
                    . $post->attributes->teaser_text
                    . '</p>';
            }

            if (isset($post->relationships->user_defined_tags)) {
                $item['categories'] = [];
                foreach ($post->relationships->user_defined_tags->data as $tag) {
                    $attrs = $this->findInclude($posts, 'post_tag', $tag->id);
                    $item['categories'][] = $attrs->value;
                }
            }

            if (isset($post->relationships->attachments)) {
                $item['enclosures'] = [];
                foreach ($post->relationships->attachments->data as $attachment) {
                    $attrs = $this->findInclude($posts, 'attachment', $attachment->id);
                    $item['enclosures'][] = $attrs->url;
                }
            }

            $this->items[] = $item;
        }
    }

    /*
     * Searches the "included" array in an API response and returns attributes
     * for the first match.
     */
    private function findInclude($data, $type, $id)
    {
        foreach ($data->included as $include) {
            if ($include->type === $type && $include->id === $id) {
                return $include->attributes;
            }
        }
    }

    private function apiGet($endpoint, $query_data = [])
    {
        $query_data['json-api-version'] = 1.0;
        $query_data['json-api-use-default-includes'] = 0;

        $url = 'https://www.patreon.com/api/'
            . $endpoint
            . '?'
            . http_build_query($query_data);

        /*
         * Accept-Language header and the CURL cipher list are for bypassing the
         * Cloudflare anti-bot protection on the Patreon API. If this ever breaks,
         * here are some other project that also deal with this:
         * https://github.com/mikf/gallery-dl/issues/342
         * https://github.com/daemionfox/patreon-feed/issues/7
         * https://www.patreondevelopers.com/t/api-returning-cloudflare-challenge/2025
         * https://github.com/splitbrain/patreon-rss/issues/4
         */
        $header = [
            'Accept-Language: en-US',
            'Content-Type: application/json'
        ];
        $opts = [
            CURLOPT_SSL_CIPHER_LIST => implode(':', [
                'DEFAULT',
                '!DHE-RSA-CHACHA20-POLY1305'
            ])
        ];

        $data = json_decode(getContents($url, $header, $opts));

        return $data;
    }

    public function getName()
    {
        if (!is_null($this->getInput('creator'))) {
            $html = getSimpleHTMLDOMCached($this->getURI());
            if ($html) {
                preg_match('#"name": "(.*)"#', $html->save(), $matches);
                return 'Patreon posts from ' . stripcslashes($matches[1]);
            } else {
                return $this->getInput('creator') . 'posts from Patreon';
            }
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('creator'))) {
            return self::URI . $this->getInput('creator');
        }

        return parent::getURI();
    }

    public function detectParameters($url)
    {
        $params = [];

        // Matches e.g. https://www.patreon.com/SomeCreator
        $regex = '/^(https?:\/\/)?(www\.)?patreon\.com\/([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['creator'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }
}
