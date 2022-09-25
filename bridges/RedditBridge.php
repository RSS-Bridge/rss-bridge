<?php

class RedditBridge extends BridgeAbstract
{
    const MAINTAINER = 'dawidsowa';
    const NAME = 'Reddit Bridge';
    const URI = 'https://www.reddit.com';
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

    public function detectParameters($url)
    {
        $parsed_url = parse_url($url);

        $host = $parsed_url['host'] ?? null;

        if ($host != 'www.reddit.com' && $host != 'old.reddit.com') {
            return null;
        }

        $path = explode('/', $parsed_url['path']);

        if ($path[1] == 'r') {
            return [
                'r' => $path[2]
            ];
        } elseif ($path[1] == 'user') {
            return [
                'u' => $path[2]
            ];
        } else {
            return null;
        }
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

    public function collectData()
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

        foreach ($subreddits as $subreddit) {
            $name = trim($subreddit);
            $values = getContents(self::URI
                    . '/search.json?q='
                    . $keywords
                    . ($user ? 'author%3A' : 'subreddit%3A')
                    . $name
                    . '&sort='
                    . $this->getInput('d')
                    . '&include_over_18=on');
            $decodedValues = json_decode($values);

            foreach ($decodedValues->data->children as $post) {
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
                $item['uri'] = $this->encodePermalink($data->permalink);

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

                    $item['content']
                        = htmlspecialchars_decode($data->body_html);
                } elseif ($data->is_self) {
                    // Text post

                    $item['content']
                        = htmlspecialchars_decode($data->selftext_html);
                } elseif (isset($data->post_hint) ? $data->post_hint == 'link' : false) {
                    // Link with preview

                    if (isset($data->media)) {
                        // Reddit embeds content for some sites (e.g. Twitter)
                        $embed = htmlspecialchars_decode(
                            $data->media->oembed->html
                        );
                    } else {
                        $embed = '';
                    }

                    $item['content'] = $this->template(
                        $data->url,
                        $data->thumbnail,
                        $data->domain
                    ) . $embed;
                } elseif (isset($data->post_hint) ? $data->post_hint == 'image' : false) {
                    // Single image

                    $item['content'] = $this->link(
                        $this->encodePermalink($data->permalink),
                        '<img src="' . $data->url . '" />'
                    );
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

                    $item['content'] = $this->template(
                        $data->url,
                        $data->preview->images[0]->resolutions[$index]->url,
                        'Video'
                    );
                } elseif (isset($data->media) ? $data->media->type == 'youtube.com' : false) {
                    // Youtube link

                    $item['content'] = $this->template(
                        $data->url,
                        $data->media->oembed->thumbnail_url,
                        'YouTube'
                    );
                } elseif (explode('.', $data->domain)[0] == 'self') {
                    // Crossposted text post
                    // TODO (optionally?) Fetch content of the original post.

                    $item['content'] = $this->link(
                        $this->encodePermalink($data->permalink),
                        'Crossposted from r/'
                        . explode('.', $data->domain)[1]
                    );
                } else {
                    // Link WITHOUT preview

                    $item['content'] = $this->link($data->url, $data->domain);
                }

                $this->items[] = $item;
            }
        }
        // Sort the order to put the latest posts first, even for mixed subreddits
        usort($this->items, function ($a, $b) {
            return $a['timestamp'] < $b['timestamp'];
        });
    }

    private function encodePermalink($link)
    {
        return self::URI . implode(
            '/',
            array_map('urlencode', explode('/', $link))
        );
    }

    private function template($href, $src, $caption)
    {
        return '<a href="' . $href . '"><figure><figcaption>'
            . $caption . '</figcaption><img src="'
            . $src . '"/></figure></a>';
    }

    private function link($href, $text)
    {
        return '<a href="' . $href . '">' . $text . '</a>';
    }
}
