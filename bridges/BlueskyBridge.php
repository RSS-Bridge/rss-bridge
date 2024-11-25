<?php

class BlueskyBridge extends BridgeAbstract
{
    const NAME = 'Bluesky';
    const URI = 'https://bsky.app';
    const DESCRIPTION = 'Fetches posts from Bluesky';
    const MAINTAINER = 'Code modified from rsshub (TonyRL https://github.com/TonyRL) and expanded';
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
            'handle' => [
                'name' => 'User Handle',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'jackdodo.bsky.social',
                'title' => 'Handle found in URL'
            ],
            'filter' => [
                'name' => 'Filter',
                'type' => 'list',
                'defaultValue' => 'posts_and_author_threads',
                'values' => [
                    'posts_and_author_threads' => 'posts_and_author_threads',
                    'posts_with_replies' => 'posts_with_replies',
                    'posts_no_replies' => 'posts_no_replies',
                    'posts_with_media' => 'posts_with_media',
                ],
                'title' => 'Combinations of post/repost types to include in response.'
            ]
        ]
    ];

    private $profile;

    public function getName()
    {
        if (isset($this->profile)) {
            return sprintf('%s (@%s) - Bluesky', $this->profile['displayName'], $this->profile['handle']);
        }
        return parent::getName();
    }

    public function getURI()
    {
        if (isset($this->profile)) {
            return self::URI . '/profile/' . $this->profile['handle'];
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
        $externalTitle = htmlspecialchars($external['title'], ENT_QUOTES, 'UTF-8');
        $externalDescription = htmlspecialchars($external['description'], ENT_QUOTES, 'UTF-8');
        $thumb = $external['thumb'] ?? null;

        if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $externalUri, $id) || preg_match('/youtu\.be\/([^\&\?\/]+)/', $externalUri, $id)) {
            $videoId = $id[1];
            $description .= "<p>External Link: <a href=\"$externalUri\">$externalTitle</a></p>";
            $description .= "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/$videoId\" frameborder=\"0\" allowfullscreen></iframe>";
        } else {
            $description .= "<p>External Link: <a href=\"$externalUri\">$externalTitle</a></p>";
            $description .= "<p>$externalDescription</p>";

            if ($thumb) {
                $thumbUrl = 'https://cdn.bsky.app/img/feed_thumbnail/plain/' . $did . '/' . $thumb['ref']['$link'] . '@jpeg';
                $description .= "<p><a href=\"$externalUri\"><img src=\"$thumbUrl\" alt=\"External Thumbnail\" /></a></p>";
            }
        }
        return $description;
    }

    private function textToDescription($text)
    {
        $text = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        $text = preg_replace('/(https?:\/\/[^\s]+)/i', '<a href="$1">$1</a>', $text);

        return $text;
    }

    public function collectData()
    {
        $handle = $this->getInput('handle');
        $filter = $this->getInput('filter') ?: 'posts_and_author_threads';

        $did = $this->resolveHandle($handle);
        $this->profile = $this->getProfile($did);
        $authorFeed = $this->getAuthorFeed($did, $filter);

        foreach ($authorFeed['feed'] as $post) {
            $item = [];
            $item['uri'] = self::URI . '/profile/' . $post['post']['author']['handle'] . '/post/' . explode('app.bsky.feed.post/', $post['post']['uri'])[1];
            $item['title'] = strtok($post['post']['record']['text'], "\n");
            $item['timestamp'] = strtotime($post['post']['record']['createdAt']);
            $item['author'] = $this->profile['displayName'];

            $description = $this->textToDescription($post['post']['record']['text']);

            // Retrieve DID for constructing image URLs
            $authorDid = $post['post']['author']['did'];

            if (isset($post['post']['record']['embed']['$type']) && $post['post']['record']['embed']['$type'] === 'app.bsky.embed.external') {
                $description .= $this->parseExternal($post['post']['record']['embed']['external'], $authorDid);
            }

            if (isset($post['post']['record']['embed']['$type']) && $post['post']['record']['embed']['$type'] === 'app.bsky.embed.video') {
                $thumbnail = $post['post']['embed']['thumbnail'] ?? null;
                if ($thumbnail) {
                                    $itemUri = self::URI . '/profile/' . $post['post']['author']['handle'] . '/post/' . explode('app.bsky.feed.post/', $post['post']['uri'])[1];
                    $description .= "<p><a href=\"$itemUri\"><img src=\"$thumbnail\" alt=\"Video Thumbnail\" /></a></p>";
                }
            }

            if (isset($post['post']['record']['embed']['$type']) && $post['post']['record']['embed']['$type'] === 'app.bsky.embed.recordWithMedia#view') {
                $thumbnail = $post['post']['embed']['media']['thumbnail'] ?? null;
                $playlist = $post['post']['embed']['media']['playlist'] ?? null;
                if ($thumbnail) {
                    $description .= "<p><video controls poster=\"$thumbnail\">";
                    $description .= "<source src=\"$playlist\" type=\"application/x-mpegURL\">";
                    $description .= 'Video source not supported</video></p>';
                }
            }

            if (!empty($post['post']['record']['embed']['images'])) {
                foreach ($post['post']['record']['embed']['images'] as $image) {
                    $linkRef = $image['image']['ref']['$link'];
                    $thumbnailUrl = $this->resolveThumbnailUrl($authorDid, $linkRef);
                    $fullsizeUrl = $this->resolveFullsizeUrl($authorDid, $linkRef);
                    $description .= "<br /><br /><a href=\"$fullsizeUrl\"><img src=\"$thumbnailUrl\" alt=\"Image\"></a>";
                }
            }

            // Enhanced handling for quote posts with images
            if (isset($post['post']['record']['embed']) && $post['post']['record']['embed']['$type'] === 'app.bsky.embed.record') {
                $quotedRecord = $post['post']['record']['embed']['record'];
                $quotedAuthor = $post['post']['embed']['record']['author']['handle'] ?? null;
                $quotedDisplayName = $post['post']['embed']['record']['author']['displayName'] ?? null;
                $quotedText = $post['post']['embed']['record']['value']['text'] ?? null;

                if ($quotedAuthor && isset($quotedRecord['uri'])) {
                    $parts = explode('/', $quotedRecord['uri']);
                    $quotedPostId = end($parts);
                    $quotedPostUri = self::URI . '/profile/' . $quotedAuthor . '/post/' . $quotedPostId;
                }

                if ($quotedText) {
                    $description .= '<hr /><strong>Quote from ' . htmlspecialchars($quotedDisplayName) . ' (@ ' . htmlspecialchars($quotedAuthor) . '):</strong><br />';
                    $description .= $this->textToDescription($quotedText);
                    if (isset($quotedPostUri)) {
                        $description .= "<p><a href=\"$quotedPostUri\">View original quote post</a></p>";
                    }
                }
            }

            if (isset($post['post']['embed']['record']['value']['embed']['images'])) {
                $quotedImages = $post['post']['embed']['record']['value']['embed']['images'];
                foreach ($quotedImages as $image) {
                    $linkRef = $image['image']['ref']['$link'] ?? null;
                    if ($linkRef) {
                        $quotedAuthorDid = $post['post']['embed']['record']['author']['did'] ?? null;
                        $thumbnailUrl = $this->resolveThumbnailUrl($quotedAuthorDid, $linkRef);
                        $fullsizeUrl = $this->resolveFullsizeUrl($quotedAuthorDid, $linkRef);
                        $description .= "<br /><br /><a href=\"$fullsizeUrl\"><img src=\"$thumbnailUrl\" alt=\"Quoted Image\"></a>";
                    }
                }
            }

            $item['content'] = $description;
            $this->items[] = $item;
        }
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
        $response = json_decode(getContents($uri), true);
        return $response;
    }

    private function resolveThumbnailUrl($authorDid, $linkRef)
    {
        return 'https://cdn.bsky.app/img/feed_thumbnail/plain/' . $authorDid . '/' . $linkRef . '@jpeg';
    }

    private function resolveFullsizeUrl($authorDid, $linkRef)
    {
        return 'https://cdn.bsky.app/img/feed_fullsize/plain/' . $authorDid . '/' . $linkRef . '@jpeg';
    }
}
