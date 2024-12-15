<?php

class BlueskyBridge extends BridgeAbstract
{
    //Initial PR by [RSSBridge contributors](https://github.com/RSS-Bridge/rss-bridge/issues/4058).
    //Modified from [©DIYgod and contributors at RSSHub](https://github.com/DIYgod/RSSHub/tree/master/lib/routes/bsky), MIT License';
    const NAME = 'Bluesky Bridge';
    const URI = 'https://bsky.app';
    const DESCRIPTION = 'Fetches posts from Bluesky';
    const MAINTAINER = 'mruac';
    const PARAMETERS = [
        [
            'data_source' => [
                'name' => 'Bluesky Data Source',
                'type' => 'list',
                'defaultValue' => 'Profile',
                'values' => [
                    'Profile' => 'getAuthorFeed',
                ],
                'title' => 'Select the type of data source to fetch from Bluesky.'
            ],
            'user_id' => [
                'name' => 'User Handle or DID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'did:plc:z72i7hdynmk6r22z27h6tvur',
                'title' => 'ATProto / Bsky.app handle or DID'
            ],
            'feed_filter' => [
                'name' => 'Feed type',
                'type' => 'list',
                'defaultValue' => 'posts_and_author_threads',
                'values' => [
                    'Posts feed' => 'posts_and_author_threads',
                    'All posts and replies' => 'posts_with_replies',
                    'Root posts only' => 'posts_no_replies',
                    'Media only' => 'posts_with_media',
                ]
            ],

            'include_reposts' => [
                'name' => 'Include Reposts?',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],

            'include_reply_context' => [
                'name' => 'Include Reply context?',
                'type' => 'checkbox'
            ],

            'verbose_title' => [
                'name' => 'Use verbose feed item titles?',
                'type' => 'checkbox'
            ]
        ]
    ];

    private $profile;

    public function getName()
    {
        if (isset($this->profile)) {
            if ($this->profile['handle'] === 'handle.invalid') {
                return sprintf('Bluesky - %s', $this->profile['displayName']);
            } else {
                return sprintf('Bluesky - %s (@%s)', $this->profile['displayName'], $this->profile['handle']);
            }
        }
        return parent::getName();
    }

    public function getURI()
    {
        if (isset($this->profile)) {
            if ($this->profile['handle'] === 'handle.invalid') {
                return self::URI . '/profile/' . $this->profile['did'];
            } else {
                return self::URI . '/profile/' . $this->profile['handle'];
            }
        }
        return parent::getURI();
    }

    public function getIcon()
    {
        if (isset($this->profile)) {
            return $this->profile['avatar'];
        }
        return parent::getIcon();
    }

    public function getDescription()
    {
        if (isset($this->profile)) {
            return $this->profile['description'];
        }
        return parent::getDescription();
    }

    private function parseExternal($external, $did)
    {
        $description = '';
        $externalUri = $external['uri'];
        $externalTitle = e($external['title']);
        $externalDescription = e($external['description']);
        $thumb = $external['thumb'] ?? null;

        if (preg_match('/http(|s):\/\/media\.tenor\.com/', $externalUri)) {
            //tenor gif embed
            $tenorInterstitial = str_replace('media.tenor.com', 'media1.tenor.com/m', $externalUri);
            $description .= "<figure><a href=\"$tenorInterstitial\"><img src=\"$externalUri\"/></a><figcaption>$externalTitle</figcaption></figure>";
        } else {
            //link embed preview
            $host = parse_url($externalUri)['host'];
            $thumbDesc = $thumb ? ('<img src="https://cdn.bsky.app/img/feed_thumbnail/plain/' . $did . '/' . $thumb['ref']['$link'] . '@jpeg"/>') : '';
            $externalDescription = strlen($externalDescription) > 0 ? "<figcaption>($host) $externalDescription</figcaption>" : '';
            $description .= '<br><blockquote><b><a href="' . $externalUri . '">' . $externalTitle . '</a></b>';
            $description .= '<figure>' . $thumbDesc . $externalDescription . '</figure></blockquote>';
        }
        return $description;
    }

    private function textToDescription($record)
    {
        if (isset($record['value'])) {
            $record = $record['value'];
        }
        $text = $record['text'];
        $text_copy = $text;
        $text = nl2br(e($text));
        if (isset($record['facets'])) {
            $facets = $record['facets'];
            foreach ($facets as $facet) {
                if ($facet['features'][0]['$type'] === 'app.bsky.richtext.facet#link') {
                    $substring = substr($text_copy, $facet['index']['byteStart'], $facet['index']['byteEnd'] - $facet['index']['byteStart']);
                    $text = str_replace($substring, '<a href="' . $facet['features'][0]['uri'] . '">' . $substring . '</a>', $text);
                }
            }
        }
        return $text;
    }

    public function collectData()
    {
        //https://bsky.app/profile/mm-gazzetta-sport.bsky.social/post/3lb5byr5fuw2c
        if ($this->getInput('post_id') !== null) {
            // $videoURL = 'https://bsky.social/xrpc/com.atproto.sync.getBlob?did=did:plc:heeihz7xdhf7dbjx4befobqw&cid=bafkreifexy4uovu5oxtbj5354il3ns5raqkgx2wilfuuejucllxfmde7iq';
            // $resolvedVideoURL = getContents($videoURL, [], [CURLOPT_FOLLOWLOCATION => 1, CURLOPT_NOBODY => 1], true);
            // print_r($resolvedVideoURL->getHeaders()['location'][0]);

            //explode and get handle
            $explode = explode('/', $this->getInput('post_id'));
            $handle = $explode[4];
            $post_id = $explode[6];
            $did = $this->resolveHandle($handle);
            //at://did:plc:xh7ydadmdldkzzi5fzccg6zg/app.bsky.feed.post/3lcnpj2ro5c2p
            //https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris=at://did:plc:hw753x7fbyzmn5ouveyepbxx/app.bsky.feed.post/3lcvcqvmyw223
            $uri = 'https://public.api.bsky.app/xrpc/app.bsky.feed.getPosts?uris=at://' . urlencode($did) . '/app.bsky.feed.post/' . $post_id;
            $response = json_decode(getContents($uri), true);
            $text = $response['posts'][0]['record']['text'];
            print_r($uri);
            print('<hr>' . $this->textToDescription($response['posts'][0]['record']));
            return;
        }

        $user_id = $this->getInput('user_id');
        $handle_match = preg_match('/(?:[a-zA-Z]*\.)+([a-zA-Z](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)/', $user_id, $handle_res); //gets the TLD in $handle_match[1]
        $did_match = preg_match('/did:plc:[a-z2-7]{24}/', $user_id); //https://github.com/did-method-plc/did-method-plc#identifier-syntax
        $exclude = ['alt', 'arpa', 'example', 'internal', 'invalid', 'local', 'localhost', 'onion']; //https://en.wikipedia.org/wiki/Top-level_domain#Reserved_domains
        if ($handle_match == true && array_search($handle_res[1], $exclude) == false) {
            //valid bsky handle
            $did = $this->resolveHandle($user_id);
        } elseif ($did_match == true) {
            //valid DID
            $did = $user_id;
        } else {
            returnClientError('Invalid ATproto handle or DID provided.');
        }

        $filter = $this->getInput('feed_filter') ?: 'posts_and_author_threads';
        $replyContext = $this->getInput('include_reply_context');

        $this->profile = $this->getProfile($did);
        $authorFeed = $this->getAuthorFeed($did, $filter);

        foreach ($authorFeed['feed'] as $post) {
            $postRecord = $post['post']['record'];

            $item = [];
            $item['uri'] = self::URI . '/profile/' . $this->fallbackAuthor($post['post']['author'], 'url') . '/post/' . explode('app.bsky.feed.post/', $post['post']['uri'])[1];
            $item['title'] = $this->getInput('verbose_title') ? $this->generateVerboseTitle($post) : strtok($postRecord['text'], "\n");
            $item['timestamp'] = strtotime($postRecord['createdAt']);
            $item['author'] = $this->fallbackAuthor($post['post']['author'], 'display');

            $postAuthorDID = $post['post']['author']['did'];
            $postAuthorHandle = $post['post']['author']['handle'] !== 'handle.invalid' ? '<i>@' . $post['post']['author']['handle'] . '</i> ' : '';
            $postDisplayName = e($post['post']['author']['displayName']) ?? '';
            $postUri = $item['uri'];

            if (Debug::isEnabled()) {
                $url = explode('/', $post['post']['uri']);
                error_log('https://bsky.app/profile/' . $url[2] . '/post/' . $url[4]);
            }

            $description = '';
            $description .= '<p>';
            //post
            $description .= $this->getPostDescription(
                $postDisplayName,
                $postAuthorHandle,
                $postUri,
                $postRecord,
                'post'
            );

            if (isset($postRecord['embed']['$type'])) {
                //post link embed
                if ($postRecord['embed']['$type'] === 'app.bsky.embed.external') {
                    $description .= $this->parseExternal($postRecord['embed']['external'], $postAuthorDID);
                } elseif (
                    $postRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                    $postRecord['embed']['media']['$type'] === 'app.bsky.embed.external'
                ) {
                    $description .= $this->parseExternal($postRecord['embed']['media']['external'], $postAuthorDID);
                }

                //post images
                if (
                    $postRecord['embed']['$type'] === 'app.bsky.embed.images' ||
                    (
                        $postRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                        $postRecord['embed']['media']['$type'] === 'app.bsky.embed.images'
                    )
                ) {
                    $images = $post['post']['embed']['images'] ?? $post['post']['embed']['media']['images'];
                    foreach ($images as $image) {
                        $description .= $this->getPostImageDescription($image);
                    }
                }

                //post video
                if (
                    $postRecord['embed']['$type'] === 'app.bsky.embed.video' ||
                    (
                        $postRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                        $postRecord['embed']['media']['$type'] === 'app.bsky.embed.video'
                    )
                ) {
                    $description .= $this->getPostVideoDescription(
                        $postRecord['embed']['video'] ?? $postRecord['embed']['media']['video'],
                        $postAuthorDID
                    );
                }
            }
            $description .= '</p>';

            //quote post
            if (
                isset($postRecord['embed']) &&
                (
                    $postRecord['embed']['$type'] === 'app.bsky.embed.record' ||
                    $postRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia'
                ) &&
                isset($post['post']['embed']['record'])
            ) {
                $description .= '<p>';
                $quotedRecord = $post['post']['embed']['record']['record'] ?? $post['post']['embed']['record'];

                if (isset($quotedRecord['notFound']) && $quotedRecord['notFound']) { //deleted post
                    $description .= 'Quoted post deleted.';
                } elseif (isset($quotedRecord['detached']) && $quotedRecord['detached']) { //detached quote
                    $uri_explode = explode('/', $quotedRecord['uri']);
                    $uri_reconstructed = self::URI . '/profile/' . $uri_explode[2] . '/post/' . $uri_explode[4];
                    $description .= '<a href="' . $uri_reconstructed . '">Quoted post detached.</a>';
                } elseif (isset($quotedRecord['blocked']) && $quotedRecord['blocked']) { //blocked by quote author
                    $description .= 'Author of quoted post has blocked OP.';
                } else {
                    $quotedAuthorDid = $quotedRecord['author']['did'];
                    $quotedDisplayName = e($quotedRecord['author']['displayName']) ?? '';
                    $quotedAuthorHandle = $quotedRecord['author']['handle'] !== 'handle.invalid' ? '<i>@' . $quotedRecord['author']['handle'] . '</i>' : '';

                    $parts = explode('/', $quotedRecord['uri']);
                    $quotedPostId = end($parts);
                    $quotedPostUri = self::URI . '/profile/' . $this->fallbackAuthor($quotedRecord['author'], 'url') . '/post/' . $quotedPostId;

                    //quoted post - post
                    $description .= $this->getPostDescription(
                        $quotedDisplayName,
                        $quotedAuthorHandle,
                        $quotedPostUri,
                        $quotedRecord,
                        'quote'
                    );

                    if (isset($quotedRecord['value']['embed']['$type'])) {
                        //quoted post - post link embed
                        if ($quotedRecord['value']['embed']['$type'] === 'app.bsky.embed.external') {
                            $description .= $this->parseExternal($quotedRecord['value']['embed']['external'], $quotedAuthorDid);
                        }

                        //quoted post - post video
                        if (
                            $quotedRecord['value']['embed']['$type'] === 'app.bsky.embed.video' ||
                            (
                                $quotedRecord['value']['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                                $quotedRecord['value']['embed']['media']['$type'] === 'app.bsky.embed.video'
                            )
                        ) {
                            $description .= $this->getPostVideoDescription(
                                $quotedRecord['value']['embed']['video'] ?? $quotedRecord['value']['embed']['media']['video'],
                                $quotedAuthorDid
                            );
                        }

                        //quoted post - post images
                        if (
                            $quotedRecord['value']['embed']['$type'] === 'app.bsky.embed.images' ||
                            (
                                $quotedRecord['value']['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                                $quotedRecord['value']['embed']['media']['$type'] === 'app.bsky.embed.images'
                            )
                        ) {
                            foreach ($quotedRecord['embeds'] as $embed) {
                                if (
                                    $embed['$type'] === 'app.bsky.embed.images#view' ||
                                    ($embed['$type'] === 'app.bsky.embed.recordWithMedia#view' && $embed['media']['$type'] === 'app.bsky.embed.images#view')
                                ) {
                                    $images = $embed['images'] ?? $embed['media']['images'];
                                    foreach ($images as $image) {
                                        $description .= $this->getPostImageDescription($image);
                                    }
                                }
                            }
                        }
                    }
                }
                $description .= '</p>';
            }

            //reply
            if ($replyContext && isset($post['reply']) && !isset($post['reply']['parent']['notFound'])) {
                $replyPost = $post['reply']['parent'];
                $replyPostRecord = $replyPost['record'];
                $description .= '<hr/>';
                $description .= '<p>';

                $replyPostAuthorDID = $replyPost['author']['did'];
                $replyPostAuthorHandle = $replyPost['author']['handle'] !== 'handle.invalid' ? '<i>@' . $replyPost['author']['handle'] . '</i> ' : '';
                $replyPostDisplayName = e($replyPost['author']['displayName']) ?? '';
                $replyPostUri = self::URI . '/profile/' . $this->fallbackAuthor($replyPost['author'], 'url') . '/post/' . explode('app.bsky.feed.post/', $replyPost['uri'])[1];

                // reply post
                $description .= $this->getPostDescription(
                    $replyPostDisplayName,
                    $replyPostAuthorHandle,
                    $replyPostUri,
                    $replyPostRecord,
                    'reply'
                );

                if (isset($replyPostRecord['embed']['$type'])) {
                    //post link embed
                    if ($replyPostRecord['embed']['$type'] === 'app.bsky.embed.external') {
                        $description .= $this->parseExternal($replyPostRecord['embed']['external'], $replyPostAuthorDID);
                    } elseif (
                        $replyPostRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                        $replyPostRecord['embed']['media']['$type'] === 'app.bsky.embed.external'
                    ) {
                        $description .= $this->parseExternal($replyPostRecord['embed']['media']['external'], $replyPostAuthorDID);
                    }

                    //post images
                    if (
                        $replyPostRecord['embed']['$type'] === 'app.bsky.embed.images' ||
                        (
                            $replyPostRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                            $replyPostRecord['embed']['media']['$type'] === 'app.bsky.embed.images'
                        )
                    ) {
                        $images = $replyPost['embed']['images'] ?? $replyPost['embed']['media']['images'];
                        foreach ($images as $image) {
                            $description .= $this->getPostImageDescription($image);
                        }
                    }

                    //post video
                    if (
                        $replyPostRecord['embed']['$type'] === 'app.bsky.embed.video' ||
                        (
                            $replyPostRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                            $replyPostRecord['embed']['media']['$type'] === 'app.bsky.embed.video'
                        )
                    ) {
                        $description .= $this->getPostVideoDescription(
                            $replyPostRecord['embed']['video'] ?? $replyPostRecord['embed']['media']['video'],
                            $replyPostAuthorDID
                        );
                    }
                }
                $description .= '</p>';

                //quote post
                if (
                    isset($replyPostRecord['embed']) &&
                    ($replyPostRecord['embed']['$type'] === 'app.bsky.embed.record' || $replyPostRecord['embed']['$type'] === 'app.bsky.embed.recordWithMedia') &&
                    isset($replyPost['embed']['record'])
                ) {
                    $description .= '<p>';
                    $replyQuotedRecord = $replyPost['embed']['record']['record'] ?? $replyPost['embed']['record'];

                    if (isset($replyQuotedRecord['notFound']) && $replyQuotedRecord['notFound']) { //deleted post
                        $description .= 'Quoted post deleted.';
                    } elseif (isset($replyQuotedRecord['detached']) && $replyQuotedRecord['detached']) { //detached quote
                        $uri_explode = explode('/', $replyQuotedRecord['uri']);
                        $uri_reconstructed = self::URI . '/profile/' . $uri_explode[2] . '/post/' . $uri_explode[4];
                        $description .= '<a href="' . $uri_reconstructed . '">Quoted post detached.</a>';
                    } elseif (isset($replyQuotedRecord['blocked']) && $replyQuotedRecord['blocked']) { //blocked by quote author
                        $description .= 'Author of quoted post has blocked OP.';
                    } else {
                        $quotedAuthorDid = $replyQuotedRecord['author']['did'];
                        $quotedDisplayName = e($replyQuotedRecord['author']['displayName']) ?? '';
                        $quotedAuthorHandle = $replyQuotedRecord['author']['handle'] !== 'handle.invalid' ? '<i>@' . $replyQuotedRecord['author']['handle'] . '</i>' : '';

                        $parts = explode('/', $replyQuotedRecord['uri']);
                        $quotedPostId = end($parts);
                        $quotedPostUri = self::URI . '/profile/' . $this->fallbackAuthor($replyQuotedRecord['author'], 'url') . '/post/' . $quotedPostId;

                        //quoted post - post
                        $description .= $this->getPostDescription(
                            $quotedDisplayName,
                            $quotedAuthorHandle,
                            $quotedPostUri,
                            $replyQuotedRecord,
                            'quote'
                        );

                        if (isset($replyQuotedRecord['value']['embed']['$type'])) {
                            //quoted post - post link embed
                            if ($replyQuotedRecord['value']['embed']['$type'] === 'app.bsky.embed.external') {
                                $description .= $this->parseExternal($replyQuotedRecord['value']['embed']['external'], $quotedAuthorDid);
                            }

                            //quoted post - post video
                            if (
                                $replyQuotedRecord['value']['embed']['$type'] === 'app.bsky.embed.video' ||
                                (
                                    $replyQuotedRecord['value']['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                                    $replyQuotedRecord['value']['embed']['media']['$type'] === 'app.bsky.embed.video'
                                )
                            ) {
                                $description .= $this->getPostVideoDescription(
                                    $replyQuotedRecord['value']['embed']['video'] ?? $replyQuotedRecord['value']['embed']['media']['video'],
                                    $quotedAuthorDid
                                );
                            }

                            //quoted post - post images
                            if (
                                $replyQuotedRecord['value']['embed']['$type'] === 'app.bsky.embed.images' ||
                                (
                                    $replyQuotedRecord['value']['embed']['$type'] === 'app.bsky.embed.recordWithMedia' &&
                                    $replyQuotedRecord['value']['embed']['media']['$type'] === 'app.bsky.embed.images'
                                )
                            ) {
                                foreach ($replyQuotedRecord['embeds'] as $embed) {
                                    if (
                                        $embed['$type'] === 'app.bsky.embed.images#view' ||
                                        ($embed['$type'] === 'app.bsky.embed.recordWithMedia#view' && $embed['media']['$type'] === 'app.bsky.embed.images#view')
                                    ) {
                                        $images = $embed['images'] ?? $embed['media']['images'];
                                        foreach ($images as $image) {
                                            $description .= $this->getPostImageDescription($image);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $description .= '</p>';
                }
            }

            $item['content'] = $description;
            $this->items[] = $item;
        }
    }

    private function getPostVideoDescription(array $video, $authorDID)
    {
        //https://video.bsky.app/watch/$did/$cid/thumbnail.jpg
        $videoCID = $video['ref']['$link'];
        $videoMime = $video['mimeType'];
        $thumbnail = "poster=\"https://video.bsky.app/watch/$authorDID/$videoCID/thumbnail.jpg\"" ?? '';
        $videoURL = "https://bsky.social/xrpc/com.atproto.sync.getBlob?did=$authorDID&cid=$videoCID";
        return "<figure><video loop $thumbnail controls src=\"$videoURL\" type=\"$videoMime\"/></figure>";
    }

    private function getPostImageDescription(array $image)
    {
        $thumbnailUrl = $image['thumb'];
        $fullsizeUrl = $image['fullsize'];
        $alt = strlen($image['alt']) > 0 ? '<figcaption>' . e($image['alt']) . '</figcaption>' : '';
        return "<figure><a href=\"$fullsizeUrl\"><img src=\"$thumbnailUrl\"></a>$alt</figure>";
    }

    private function getPostDescription(
        string $postDisplayName,
        string $postAuthorHandle,
        string $postUri,
        array $postRecord,
        string $type
    ) {
        $description = '';
        if ($type === 'quote') {
            // Quoted post/reply from bbb @bbb.com:
            $postType = isset($postRecord['reply']) ? 'reply' : 'post';
            $description .= "<a href=\"$postUri\">Quoted $postType</a> from <b>$postDisplayName</b> $postAuthorHandle:<br>";
        } elseif ($type === 'reply') {
            // Replying to aaa @aaa.com's post/reply:
            $postType = isset($postRecord['reply']) ? 'reply' : 'post';
            $description .= "Replying to <b>$postDisplayName</b> $postAuthorHandle's <a href=\"$postUri\">$postType</a>:<br>";
        } else {
            // aaa @aaa.com posted:
            $description .= "<b>$postDisplayName</b> $postAuthorHandle <a href=\"$postUri\">posted</a>:<br>";
        }
        $description .= $this->textToDescription($postRecord);
        return $description;
    }

    //used if handle verification fails, fallsback to displayName or DID depending on context.
    private function fallbackAuthor($author, $reason)
    {
        if ($author['handle'] === 'handle.invalid') {
            switch ($reason) {
                case 'url':
                    return $author['did'];
                case 'display':
                    return e($author['displayName']);
            }
        }
        return $author['handle'];
    }

    private function generateVerboseTitle($post)
    {
        //use "Post by A, replying to B, quoting C" instead of post contents
        $title = '';
        if (isset($post['reason']) && str_contains($post['reason']['$type'], 'reasonRepost')) {
            $title .= 'Repost by ' . $this->fallbackAuthor($post['reason']['by'], 'display') . ', post by ' . $this->fallbackAuthor($post['post']['author'], 'display');
        } else {
            $title .= 'Post by ' . $this->fallbackAuthor($post['post']['author'], 'display');
        }

        if (isset($post['reply'])) {
            if (isset($post['reply']['parent']['blocked'])) {
                $replyAuthor = 'blocked user';
            } elseif (isset($post['reply']['parent']['notFound'])) {
                $replyAuthor = 'deleted post';
            } else {
                $replyAuthor = $this->fallbackAuthor($post['reply']['parent']['author'], 'display');
            }
            $title .= ', replying to ' . $replyAuthor;
        }
        if (isset($post['post']['embed']) && isset($post['post']['embed']['record'])) {
            if (isset($post['post']['embed']['record']['blocked'])) {
                $quotedAuthor = 'blocked user';
            } elseif (isset($post['post']['embed']['record']['notFound'])) {
                $quotedAuthor = 'deleted post';
            } elseif (isset($post['post']['embed']['record']['detached'])) {
                $quotedAuthor = 'detached post';
            } else {
                $quotedAuthor = $this->fallbackAuthor($post['post']['embed']['record']['record']['author'] ?? $post['post']['embed']['record']['author'], 'display');
            }
            $title .= ', quoting ' . $quotedAuthor;
        }
        return $title;
    }

    private function resolveHandle($handle)
    {
        $uri = 'https://public.api.bsky.app/xrpc/com.atproto.identity.resolveHandle?handle=' . urlencode($handle);
        $response = json_decode(getContents($uri), true);
        return $response['did'];
    }

    private function getProfile($did)
    {
        $uri = 'https://public.api.bsky.app/xrpc/app.bsky.actor.getProfile?actor=' . urlencode($did);
        $response = json_decode(getContents($uri), true);
        return $response;
    }

    private function getAuthorFeed($did, $filter)
    {
        $uri = 'https://public.api.bsky.app/xrpc/app.bsky.feed.getAuthorFeed?actor=' . urlencode($did) . '&filter=' . urlencode($filter) . '&limit=30';
        if (Debug::isEnabled()) {
            error_log($uri);
        }
        $response = json_decode(getContents($uri), true);
        return $response;
    }
}
