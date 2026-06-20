<?php

declare(strict_types=1);

class TrixiebooruBridge extends BridgeAbstract
{
    const NAME = 'Trixiebooru';
    const URI = 'https://trixiebooru.org/';
    const DESCRIPTION = 'Returns images and videos from Trixiebooru search';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 1800; // 30 minutes

    const PARAMETERS = [
        'Global' => [
            'q' => [
                'name' => 'Query (Tags)',
                'required' => true,
                'title' => 'Tags for search, separated by commas or spaces (e.g., "tag1, tag2" or "tag1 tag2")'
            ],
            'exclude_tags' => [
                'name' => 'Blacklist (Exclude tags)',
                'required' => false,
                'title' => 'Tags for exclude, separated by commas or spaces (e.g., "tag1, tag2" or "tag1 tag2"). Posts containing ANY of these tags will be excluded from the feed.'
            ],
            'f' => [
                'name' => 'Content Filter',
                'type' => 'list',
                'values' => [
                    'Everything (No limits, shows ALL)' => 56027,
                    '18+ R34 (Explicit allowed, hides gore/AI)' => 37432,
                    '18+ Dark (Gore/grimdark allowed, hides explicit)' => 37429,
                    'Legacy Default (Old safe mode, hides explicit)' => 37431,
                    'Default (Modern safe, hides non-art & adult)' => 100073
                ],
                'defaultValue' => 56027
            ],
            'sf' => [
                'name' => 'Sort By',
                'type' => 'list',
                'values' => [
                    'Creation date' => 'created_at',
                    'Score' => 'score',
                    'Wilson score' => 'wilson_score',
                    'Favorites' => 'faves',
                    'Upvotes' => 'upvotes',
                    'Views' => 'views',
                    'Comments' => 'comments',
                    'Random' => 'random'
                ],
                'defaultValue' => 'created_at'
            ],
            'sd' => [
                'name' => 'Sort Direction',
                'type' => 'list',
                'values' => [
                    'Descending' => 'desc',
                    'Ascending' => 'asc'
                ],
                'defaultValue' => 'desc'
            ],
            'limit' => [
                'name' => 'Posts Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of posts to fetch (API allows up to 50)',
                'defaultValue' => 10
            ],
            'hide_tags' => [
                'name' => 'Hide tags',
                'type' => 'checkbox',
                'title' => 'Check this box to completely hide the tags list from the post content',
                'defaultValue' => false
            ]
        ]
    ];

    public function detectParameters($url)
    {
        $params = [];

        $regex = '/^(https?:\/\/)?(www\.)?trixiebooru\.org\/search(?:\?.*)?/';
        if (preg_match($regex, $url) > 0) {
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['q'])) {
                    $params['q'] = $queryParams['q'];
                }
                if (isset($queryParams['sf'])) {
                    $params['sf'] = $queryParams['sf'];
                }
                if (isset($queryParams['sd'])) {
                    $params['sd'] = $queryParams['sd'];
                }
                return $params;
            }
        }

        $regex = '/^(https?:\/\/)?(www\.)?trixiebooru\.org\/tags\/([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['q'] = str_replace('-colon-', ':', urldecode($matches[3]));
            return $params;
        }

        return null;
    }

    private function getNormalizedQuery()
    {
        $q = trim($this->getInput('q') ?? '');
        $excludeTags = trim($this->getInput('exclude_tags') ?? '');

        $tagsArray = [];

        if (!empty($q)) {
            $qNormalized = preg_replace('/[\s,]+/', ', ', $q);
            $tagsArray = array_filter(array_map('trim', explode(',', $qNormalized)));
        }

        if (!empty($excludeTags)) {
            $excludesNormalized = preg_replace('/[\s,]+/', ', ', $excludeTags);
            $excludesArray = array_filter(array_map('trim', explode(',', $excludesNormalized)));
            foreach ($excludesArray as $tag) {
                $cleanTag = ltrim($tag, '-');
                if (!empty($cleanTag)) {
                    $tagsArray[] = '-' . $cleanTag;
                }
            }
        }

        return implode(', ', $tagsArray);
    }

    public function getName()
    {
        $q = $this->getNormalizedQuery();
        if (!empty($q)) {
            return self::NAME . ': ' . $q;
        }
        return parent::getName();
    }

    public function getURI()
    {
        $q = $this->getNormalizedQuery();
        if (!empty($q)) {
            $url = self::URI . 'search?';
            $params = [
                'q' => $q,
                'sf' => $this->getInput('sf') ?? 'created_at',
                'sd' => $this->getInput('sd') ?? 'desc'
            ];
            if (!is_null($this->getInput('f'))) {
                $params['filter_id'] = $this->getInput('f');
            }
            return $url . http_build_query($params);
        }
        return parent::getURI();
    }

    public function collectData()
    {
        $q = $this->getNormalizedQuery();
        $f = $this->getInput('f') ?? 56027;
        $sf = $this->getInput('sf') ?? 'created_at';
        $sd = $this->getInput('sd') ?? 'desc';
        $limit = $this->getInput('limit') ?? 20;
        $hideTags = $this->getInput('hide_tags') ?? false;

        $limit = min(50, max(1, (int)$limit));

        $query = urlencode($q);
        $filter = urlencode($f);

        $apiUrl = sprintf(
            '%sapi/v1/json/search/images?filter_id=%s&q=%s&sf=%s&sd=%s&per_page=%d',
            self::URI,
            $filter,
            $query,
            $sf,
            $sd,
            $limit
        );

        $jsonString = getContents($apiUrl);
        $json = json_decode($jsonString);

        if (!$json || !isset($json->images)) {
            throwClientException('No images found or invalid API response.');
        }

        foreach ($json->images as $post) {
            $item = [];

            $postUri = self::URI . 'images/' . $post->id;
            $item['uri'] = $postUri;

            $artist = '';
            if (!empty($post->tags) && is_array($post->tags)) {
                foreach ($post->tags as $tag) {
                    if (strpos($tag, 'artist:') === 0) {
                        $artist = substr($tag, 7);
                        break;
                    }
                }
            }

            if (!empty($artist)) {
                $item['title'] = sprintf('Image %s by %s', $post->id, $artist);
            } else {
                $item['title'] = sprintf('Image %s', $post->id);
            }

            $item['timestamp'] = strtotime($post->created_at);
            $item['author'] = $post->uploader ?? 'Anonymous';

            $html = '';

            $isVideo = false;
            if (isset($post->mime_type) && strpos($post->mime_type, 'video/') === 0) {
                $isVideo = true;
            } elseif (isset($post->format) && in_array($post->format, ['webm', 'mp4'])) {
                $isVideo = true;
            }

            $mediaUrl = $post->representations->full ?? '';
            $thumbUrl = $post->representations->medium ?? $post->representations->small ?? $mediaUrl;

            if ($isVideo && !empty($mediaUrl)) {
                $html .= sprintf(
                    '<p><a href="%s"><video controls loop muted preload="metadata" style="max-width:100%%;height:auto;" src="%s"></video></a></p>',
                    $postUri,
                    htmlspecialchars($mediaUrl)
                );
            } elseif (!empty($thumbUrl)) {
                $html .= sprintf(
                    '<p><a href="%s"><img src="%s" alt="Image %s"></a></p>',
                    $postUri,
                    htmlspecialchars($thumbUrl),
                    $post->id
                );
            }

            if (!empty($post->description)) {
                $cleanDesc = $this->cleanDescription($post->description);
                if (!empty($cleanDesc)) {
                    $html .= sprintf(
                        '<p><b>Description:</b><br>%s</p>',
                        nl2br(htmlspecialchars($cleanDesc))
                    );
                }
            }

            $html .= sprintf(
                '<p><b>Size:</b> %sx%s | <b>Score:</b> %s</p>',
                $post->width ?? '?',
                $post->height ?? '?',
                $post->score ?? 'N/A'
            );

            if (!empty($post->source_urls)) {
                $sources = '';
                foreach ($post->source_urls as $source) {
                    $sources .= sprintf(
                        '<a href="%s" rel="noopener noreferrer">%s</a><br>',
                        htmlspecialchars($source),
                        htmlspecialchars($source)
                    );
                }
                $html .= sprintf('<p><b>Sources:</b><br>%s</p>', $sources);
            }

            if (!$hideTags && !empty($post->tags) && is_array($post->tags)) {
                $html .= sprintf(
                    '<p><b>Tags:</b> %s</p>',
                    htmlspecialchars(implode(', ', $post->tags))
                );
            }

            $item['content'] = $html;

            $this->items[] = $item;
        }
    }

    private function cleanDescription($text)
    {
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);
        $text = str_replace('*', '', $text);
        $text = str_replace('\\', '', $text);
        $text = preg_replace('/^#+\s+/m', '', $text);
        $text = preg_replace('/^>\s+/m', '', $text);
        $text = preg_replace('/```([^`]+)```/', '$1', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text);
        $text = preg_replace('/[^\x00-\x7F\x{0400}-\x{04FF}\p{P}\s]/u', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/ {2,}/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}