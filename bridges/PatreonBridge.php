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
                'poll.choices',
                //'poll.current_user_responses.user',
                //'poll.current_user_responses.choice',
                //'poll.current_user_responses.poll',
                //'access_rules.tier.null',
                'images.null',
                'audio.null',
                // 'user.null',
                'attachments.null',
                'audio_preview.null',
                'poll.choices.null'
                // 'poll.current_user_responses.null'
            ]),
            'fields' => [
                'post' => implode(',', [
                    //'change_visibility_at',
                    //'comment_count',
                    'content',
                    //'current_user_can_delete',
                    //'current_user_can_view',
                    //'current_user_has_liked',
                    'embed',
                    'image',
                    //'is_paid',
                    //'like_count',
                    //'min_cents_pledged_to_view',
                    //'patreon_url',
                    //'patron_count',
                    //'pledge_url',
                    // 'post_file',
                    // 'post_metadata',
                    'post_type',
                    'published_at',
                    'teaser_text',
                    //'thumbnail_url',
                    'title',
                    //'upgrade_url',
                    'url',
                    //'was_posted_by_campaign_owner'
                    // 'content_teaser_text',
                    // 'current_user_can_report',
                    'thumbnail',
                    // 'video_preview'
                ]),
                'user' => implode(',', [
                    //'image_url',
                    'full_name',
                    //'url'
                ]),
                'media' => implode(',', [
                    'id',
                    'image_urls',
                    'download_url',
                    'metadata',
                    'file_name',
                    'mimetype',
                    'size_bytes'
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
            )->attributes;
            $item['author'] = $user->full_name;

            //image, video, audio, link (featured post content)
            switch ($post->attributes->post_type) {
                case 'audio_file':
                    //check if download_url is null before assigning $audio
                    $id = $post->relationships->audio->data->id ?? null;
                    if (isset($id)) {
                        $audio = $this->findInclude($posts, 'media', $id)->attributes ?? null;
                    }
                    if (!isset($audio->download_url)) { //if not unlocked
                        $id = $post->relationships->audio_preview->data->id ?? null;
                        if (isset($id)) {
                            $audio = $this->findInclude($posts, 'media', $id)->attributes ?? null;
                        }
                    }
                    $thumbnail = $post->attributes->thumbnail->large ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->thumbnail->url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->thumb_url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->url;
                    $audio_filename = $audio->file_name ?? $item['title'];
                    $download_url = $audio->download_url ?? $item['uri'];
                    $item['content'] .= "<p><a href\"{$download_url}\"><img src=\"{$thumbnail}\"><br/>🎧 {$audio_filename}</a><br/>";
                    if ($download_url !== $item['uri']) {
                        $item['enclosures'][] = $download_url;
                        $item['content'] .= "<audio controls src=\"{$download_url}\"></audio>";
                    }
                    $item['content'] .= '</p>';
                    break;

                case 'video_embed':
                    $thumbnail = $post->attributes->thumbnail->large ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->thumbnail->url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->thumb_url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->url;
                    $item['content'] .= "<p><a href=\"{$item['uri']}\">🎬 {$item['title']}<br><img src=\"{$thumbnail}\"></a></p>";
                    break;

                case 'video_external_file':
                    $thumbnail = $post->attributes->thumbnail->large ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->thumbnail->url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->thumb_url ?? null;
                    $thumbnail = $thumbnail ?? $post->attributes->image->url;
                    $item['content'] .= "<p><a href=\"{$item['uri']}\">🎬 {$item['title']}<br><img src=\"{$thumbnail}\"></a></p>";
                    break;

                case 'image_file':
                    $item['content'] .= '<p>';
                    foreach ($post->relationships->images->data as $key => $image) {
                        $image = $this->findInclude($posts, 'media', $image->id)->attributes;
                        $image_fullres = $image->download_url ?? $image->image_urls->url ?? $image->image_urls->original;
                        $filename = $image->file_name ?? '';
                        $image_url = $image->image_urls->url ?? $image->image_urls->original;
                        $item['enclosures'][] = $image_fullres;
                        $item['content'] .= "<a href=\"{$image_fullres}\">{$filename}<br/><img src=\"{$image_url}\"></a><br/><br/>";
                    }
                    $item['content'] .= '</p>';
                    break;

                case 'link':
                    //make it locked safe
                    if (isset($post->attributes->embed)) {
                        $embed = $post->attributes->embed;
                        $thumbnail = $post->attributes->image->large_url ?? $post->attributes->image->thumb_url ?? $post->attributes->image->url;
                        $item['content'] .= '<p><table>';
                        $item['content'] .= "<tr><td><a href=\"{$embed->url}\"><img src=\"{$thumbnail}\"></a></td></tr>";
                        $item['content'] .= "<tr><td><b>{$embed->subject}</b></td></tr>";
                        $item['content'] .= "<tr><td>{$embed->description}</td></tr>";
                        $item['content'] .= '</table></p><hr/>';
                    }
                    break;
            }

            //content of the post
            if (isset($post->attributes->content)) {
                $item['content'] .= $post->attributes->content;
            } elseif (isset($post->attributes->teaser_text)) {
                $item['content'] .= '<p>'
                    . $post->attributes->teaser_text;
                if (strlen($post->attributes->teaser_text) === 140) {
                    $item['content'] .= '…';
                }
                $item['content'] .= '</p>';
            }

            //post tags
            if (isset($post->relationships->user_defined_tags)) {
                $item['categories'] = [];
                foreach ($post->relationships->user_defined_tags->data as $tag) {
                    $attrs = $this->findInclude($posts, 'post_tag', $tag->id)->attributes;
                    $item['categories'][] = $attrs->value;
                }
            }

            //poll
            if (isset($post->relationships->poll->data)) {
                $poll = $this->findInclude($posts, 'poll', $post->relationships->poll->data->id);
                $item['content'] .= "<p><table><tr><th><b>Poll: {$poll->attributes->question_text}</b></th></tr>";
                foreach ($poll->relationships->choices->data as $key => $poll_option) {
                    $poll_option = $this->findInclude($posts, 'poll_choice', $poll_option->id);
                    $poll_option_text = $poll_option->attributes->text_content ?? null;
                    if (isset($poll_option_text)) {
                        $item['content'] .= "<tr><td><a href=\"{$item['uri']}\">{$poll_option_text}</a></td></tr>";
                    }
                }
                $item['content'] .= '</table></p>';
            }


            //post attachments
            if (
                isset($post->relationships->attachments->data) &&
                sizeof($post->relationships->attachments->data) > 0
            ) {
                $item['enclosures'] = [];
                $item['content'] .= '<hr><p><b>Attachments:</b><ul>';
                foreach ($post->relationships->attachments->data as $attachment) {
                    $attrs = $this->findInclude($posts, 'attachment', $attachment->id)->attributes;
                    $filename = $attrs->name;
                    $n = strrpos($filename, '.');
                    $ext = ($n === false) ? '' : substr($filename, $n);
                    $item['enclosures'][] = $attrs->url . '#' . $ext;
                    $item['content'] .= '<li><a href="' . $attrs->url . '">' . $filename . '</a></li>';
                }
                $item['content'] .= '</ul></p>';
            }

            $this->items[] = $item;
        }
    }

    /*
     * Searches the "included" array in an API response and returns the result for the first match.
     * A result will include attributes containing further details of the included object
     * (e.g. an audio object), and an optional relationships object that links to more "included"
     * objects. (e.g. a poll object with related poll_choice(s))
     */
    private function findInclude($data, $type, $id)
    {
        foreach ($data->included as $include) {
            if ($include->type === $type && $include->id === $id) {
                return $include;
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
