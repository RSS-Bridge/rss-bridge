<?php

/**
 * This bridge does NOT use reddit's official rss feeds.
 *
 * This bridge uses reddit's json api: https://old.reddit.com/search.json?q=
 */
class RedditBridge extends BridgeAbstract
{
    const MAINTAINER = 'dawidsowa';
    const NAME = 'Reddit Bridge';
    const URI = 'https://old.reddit.com';
    const DESCRIPTION = 'Return hot submissions from Reddit';

    const PARAMETERS = [
        'global' => [
            'score' => [
                'name' => 'Minimal score',
                'required' => false,
                'type' => 'number',
                'exampleValue' => 100,
                'title' => 'Filter out posts with lower score'
            ],
            'd' => [
                'name' => 'Sort By',
                'type' => 'list',
                'title' => 'Sort by new, hot, top or relevancy',
                'values' => [
                    'Hot' => 'hot',
                    'Relevance' => 'relevance',
                    'New' => 'new',
                    'Top' => 'top'
                ],
                'defaultValue' => 'Hot'
            ],
            'search' => [
                'name' => 'Keyword search',
                'required' => false,
                'exampleValue' => 'cats, dogs',
                'title' => 'Keyword search, separated by commas'
            ]
        ],
        'single' => [
            'r' => [
                'name' => 'SubReddit',
                'required' => true,
                'exampleValue' => 'selfhosted',
                'title' => 'SubReddit name'
            ],
            'f' => [
                'name' => 'Flair',
                'required' => false,
                'exampleValue' => 'Proxy',
                'title' => 'Flair filter'
            ]
        ],
        'multi' => [
            'rs' => [
                'name' => 'SubReddits',
                'required' => true,
                'exampleValue' => 'selfhosted, php',
                'title' => 'SubReddit names, separated by commas'
            ]
        ],
        'user' => [
            'u' => [
                'name' => 'User',
                'required' => true,
                'exampleValue' => 'shwikibot',
                'title' => 'User name'
            ],
            'comments' => [
                'type' => 'checkbox',
                'name' => 'Comments',
                'title' => 'Whether to return comments',
                'defaultValue' => false
            ]
        ]
    ];

    public function collectData()
    {
        $forbiddenKey = 'reddit_forbidden';
        if ($this->cache->get($forbiddenKey)) {
            throw new HttpException('403 Forbidden', 403);
        }

        $rateLimitKey = 'reddit_rate_limit';
        if ($this->cache->get($rateLimitKey)) {
            throw new HttpException('429 Too Many Requests', 429);
        }

        try {
            $this->collectDataInternal();
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                // 403 Forbidden
                // This can possibly mean that reddit has permanently blocked this server's ip address
                $this->cache->set($forbiddenKey, true, 60 * 61);
            }
            if ($e->getCode() === 429) {
                $this->cache->set($rateLimitKey, true, 60 * 16);
            }
            throw $e;
        }
    }

    private function collectDataInternal(): void
    {
        $user = false;
        $comments = false;
        $section = $this->getInput('d');

        switch ($this->queriedContext) {
            case 'single':
                $subreddits[] = $this->getInput('r');
                break;
            case 'multi':
                $subreddits = explode(',', $this->getInput('rs'));
                break;
            case 'user':
                $subreddits[] = $this->getInput('u');
                $user = true;
                $comments = $this->getInput('comments');
                break;
        }

        if (!($this->getInput('search') === '')) {
            $keywords = $this->getInput('search');
            $keywords = str_replace([',', ' '], '%20', $keywords);
            $keywords = $keywords . '%20';
        } else {
            $keywords = '';
        }

        if (!empty($this->getInput('f')) && $this->queriedContext == 'single') {
            $flair = $this->getInput('f');
            $flair = str_replace(' ', '%20', $flair);
            $flair = 'flair%3A%22' . $flair . '%22%20';
        } else {
            $flair = '';
        }

        foreach ($subreddits as $subreddit) {
            $name = trim($subreddit);
            $url = self::URI
                . '/search.json?q='
                . $keywords
                . $flair
                . ($user ? 'author%3A' : 'subreddit%3A')
                . $name
                . '&sort='
                . $this->getInput('d')
                . '&include_over_18=on';

            $version = 'v0.0.1';
            $useragent = "rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)";
            $json = getContents($url, ['User-Agent: ' . $useragent]);
            $parsedJson = Json::decode($json, false);

            foreach ($parsedJson->data->children as $post) {
                if ($post->kind == 't1' && !$comments) {
                    continue;
                }

                $data = $post->data;

                if ($data->score < $this->getInput('score')) {
                    continue;
                }

                $item = [];
                $item['author'] = $data->author;
                $item['uid'] = $data->id;
                $item['timestamp'] = $data->created_utc;
                $item['uri'] = $this->urlEncodePathParts($data->permalink);

                $item['categories'] = [];

                if ($post->kind == 't1') {
                    $item['title'] = 'Comment: ' . $data->link_title;
                } else {
                    $item['title'] = $data->title;

                    $item['categories'][] = $data->link_flair_text;
                    $item['categories'][] = $data->pinned ? 'Pinned' : null;
                    $item['categories'][] = $data->spoiler ? 'Spoiler' : null;
                }

                $item['categories'][] = $data->over_18 ? 'NSFW' : null;
                $item['categories'] = array_filter($item['categories']);

                if ($post->kind == 't1') {
                    // Comment

                    $item['content'] = htmlspecialchars_decode($data->body_html);
                } elseif ($data->is_self) {
                    // Text post

                    $item['content'] = htmlspecialchars_decode($data->selftext_html);
                } elseif (isset($data->post_hint) && $data->post_hint == 'link') {
                    // Link with preview

                    if (isset($data->media)) {
                        // todo: maybe switch on the type
                        if (isset($data->media->oembed->html)) {
                            // Reddit embeds content for some sites (e.g. Twitter)
                            $embed = htmlspecialchars_decode($data->media->oembed->html);
                        } else {
                            $embed = '';
                        }
                    } else {
                        $embed = '';
                    }

                    $item['content'] = $this->createFigureLink($data->url, $data->thumbnail, $data->domain) . $embed;
                } elseif (isset($data->post_hint) && $data->post_hint == 'image') {
                    // Single image

                    $item['content'] = $this->createLink($this->urlEncodePathParts($data->permalink), '<img src="' . $data->url . '" />');
                } elseif ($data->is_gallery ?? false) {
                    // Multiple images

                    $images = [];
                    foreach ($data->gallery_data->items as $media) {
                        $id = $media->media_id;
                        $type = $data->media_metadata->$id->m == 'image/gif' ? 'gif' : 'u';
                        $src = $data->media_metadata->$id->s->$type;
                        $images[] = '<figure><img src="' . $src . '"/></figure><br>';
                    }

                    $item['content'] = implode('', $images);
                } elseif ($data->is_video) {
                    // Video

                    // Higher index -> Higher resolution
                    end($data->preview->images[0]->resolutions);
                    $index = key($data->preview->images[0]->resolutions);

                    $item['content'] = $this->createFigureLink($data->url, $data->preview->images[0]->resolutions[$index]->url, 'Video');
                } elseif (isset($data->media) && $data->media->type == 'youtube.com') {
                    // Youtube link
                    $item['content'] = $this->createFigureLink($data->url, $data->media->oembed->thumbnail_url, 'YouTube');
                    //$item['content'] = htmlspecialchars_decode($data->media->oembed->html);
                } elseif (explode('.', $data->domain)[0] == 'self') {
                    // Crossposted text post
                    // TODO (optionally?) Fetch content of the original post.
                    $item['content'] = $this->createLink($this->urlEncodePathParts($data->permalink), 'Crossposted from r/' . explode('.', $data->domain)[1]);
                } else {
                    // Link WITHOUT preview
                    $item['content'] = $this->createLink($data->url, $data->domain);
                }

                $this->items[] = $item;
            }
        }
        // Sort the order to put the latest posts first, even for mixed subreddits
        usort($this->items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
    }

    public function getIcon()
    {
        return 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-96x96.png';
    }

    public function getName()
    {
        if ($this->queriedContext == 'single') {
            return 'Reddit r/' . $this->getInput('r');
        } elseif ($this->queriedContext == 'user') {
            return 'Reddit u/' . $this->getInput('u');
        } else {
            return self::NAME;
        }
    }

    private function urlEncodePathParts($link)
    {
        return self::URI . implode('/', array_map('urlencode', explode('/', $link)));
    }

    private function createFigureLink($href, $src, $caption)
    {
        return sprintf('<a href="%s"><figure><figcaption>%s</figcaption><img src="%s"/></figure></a>', $href, $caption, $src);
    }

    private function createLink($href, $text)
    {
        return sprintf('<a href="%s">%s</a>', $href, $text);
    }

    public function detectParameters($url)
    {
        try {
            $urlObject = Url::fromString($url);
        } catch (UrlException $e) {
            return null;
        }

        $host = $urlObject->getHost();
        $path = $urlObject->getPath();

        $pathSegments = explode('/', $path);

        if ($host !== 'www.reddit.com' && $host !== 'old.reddit.com') {
            return null;
        }

        if ($pathSegments[1] == 'r') {
            return [
                'context' => 'single',
                'r' => $pathSegments[2],
            ];
        } elseif ($pathSegments[1] == 'user') {
            return [
                'context' => 'user',
                'u' => $pathSegments[2],
            ];
        } else {
            return null;
        }
    }
}
