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
    const CACHE_TIMEOUT = 60 * 60 * 2; // 2h
    const DESCRIPTION = 'Return hot submissions from Reddit';

    const PARAMETERS = [
        'global' => [
            'score' => [
                'name' => 'Minimal score',
                'required' => false,
                'type' => 'number',
                'exampleValue' => 100,
                'title' => 'Filter out posts with lower score. Set to -1 to disable. If both score and comments are set, an OR is applied.',
            ],
            'min_comments' => [
                'name' => 'Minimal number of comments',
                'required' => false,
                'type' => 'number',
                'exampleValue' => 100,
                'title' => 'Filter out posts with lower number of comments. Set to -1 to disable. If both score and comments are set, an OR is applied.',
                'defaultValue' => -1
            ],
            'd' => [
                'name' => 'Sort By',
                'type' => 'list',
                'title' => 'Sort by new, hot, top or relevancy',
                'values' => [
                    'Hot' => 'hot',
                    'Relevance' => 'relevance',
                    'New' => 'new',
                    'Top' => 'top',
                    'Comments' => 'comments',
                ],
                'defaultValue' => 'Hot'
            ],
            't' => [
                'name' => 'Time',
                'type' => 'list',
                'title' => 'Sort by new, hot, top or relevancy',
                'values' => [
                    'All' => 'all',
                    'Year' => 'year',
                    'Month' => 'month',
                    'Week' => 'week',
                    'Day' => 'day',
                    'Hour' => 'hour',
                ],
                'defaultValue' => 'week'
            ],
            'search' => [
                'name' => 'Keyword search',
                'required' => false,
                'exampleValue' => 'cats, dogs',
                'title' => 'Keyword search, separated by commas'
            ],
            'frontend' => [
                'type' => 'list',
                'name' => 'frontend',
                'title' => 'choose frontend for  reddit',
                'values' => [
                    'old.reddit.com' => 'https://old.reddit.com',
                    'reddit.com' => 'https://reddit.com',
                    'libreddit.kavin.rocks' => 'https://libreddit.kavin.rocks',
                ]
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
            throw new RateLimitException();
        }

        $rateLimitKey = 'reddit_rate_limit';
        if ($this->cache->get($rateLimitKey)) {
            throw new RateLimitException();
        }

        try {
            $this->collectDataInternal();
        } catch (HttpException $e) {
            if ($e->getCode() === 403) {
                // 403 Forbidden
                // This can possibly mean that reddit has permanently blocked this server's ip address
                $this->cache->set($forbiddenKey, true, 60 * 61);
                throw new RateLimitException();
            } elseif ($e->getCode() === 429) {
                $this->cache->set($rateLimitKey, true, 60 * 61);
                throw new RateLimitException();
            }
            throw $e;
        }
    }

    private function collectDataInternal(): void
    {
        $user = false;
        $comments = false;
        $frontend = $this->getInput('frontend');
        if ($frontend == '') {
            $frontend = 'https://old.reddit.com';
        }
        $section = $this->getInput('d');
        $time = $this->getInput('t');

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

        $search = $this->getInput('search');
        $flareInput = $this->getInput('f');

        foreach ($subreddits as $subreddit) {
            $version = 'v0.0.2';
            $useragent = "rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)";
            $url = self::createUrl($search, $flareInput, $subreddit, $user, $section, $time, $this->queriedContext);

            $response = getContents($url, ['User-Agent: ' . $useragent], [], true);

            $json = $response->getBody();

            $parsedJson = Json::decode($json, false);

            foreach ($parsedJson->data->children as $post) {
                if ($post->kind == 't1' && !$comments) {
                    continue;
                }

                $data = $post->data;

                $min_score = $this->getInput('score');
                $min_comments = $this->getInput('min_comments');
                if ($min_score >= 0 && $min_comments >= 0) {
                    if ($data->num_comments < $min_comments || $data->score < $min_score) {
                        continue;
                    }
                } elseif ($min_score >= 0) {
                    if ($data->score < $min_score) {
                        continue;
                    }
                } elseif ($min_comments >= 0) {
                    if ($data->num_comments < $min_comments) {
                        continue;
                    }
                }

                $item = [];
                $item['author'] = $data->author;
                $item['uid'] = $data->id;
                $item['timestamp'] = $data->created_utc;
                $item['uri'] = $this->urlEncodePathParts($data->permalink);

                if ($frontend != 'https://old.reddit.com') {
                    $item['uri'] = preg_replace('#^https://old\.reddit\.com#', $frontend, $item['uri']);
                }

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
                } elseif ($data->is_self && isset($data->selftext_html)) {
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

                    if ($data->media->reddit_video) {
                        $item['content'] = $this->createVideoContent($data->media->reddit_video);
                    } else {
                        // Higher index -> Higher resolution
                        end($data->preview->images[0]->resolutions);
                        $index = key($data->preview->images[0]->resolutions);
                        $item['content'] = $this->createFigureLink($data->url, $data->preview->images[0]->resolutions[$index]->url, 'Video');
                    }
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

    public static function createUrl($search, $flareInput, $subreddit, bool $user, $section, $time, $queriedContext): string
    {
        if ($search === '') {
            $keywords = '';
        } else {
            $keywords = $search;
            $keywords = str_replace([',', ' '], ' ', $keywords);
            $keywords = $keywords . ' ';
        }

        if ($flareInput && $queriedContext == 'single') {
            $flair = $flareInput;
            $flair = str_replace([',', ' '], ' ', $flair);
            $flair = 'flair:"' . $flair . '" ';
        } else {
            $flair = '';
        }
        $name = trim($subreddit);
        $query = [
            'q' => $keywords . $flair . ($user ? 'author:' : 'subreddit:') . $name,
            'sort' => $section,
            'include_over_18' => 'on',
            't' => $time
        ];
        return 'https://old.reddit.com/search.json?' . http_build_query($query);
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

    private function createVideoContent(\stdClass $video): string
    {
        return <<<HTML
            <video width="$video->width" height="$video->height" controls>
                <source src="$video->fallback_url" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        HTML;
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
