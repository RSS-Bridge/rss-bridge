<?php

declare(strict_types=1);

class PhilomenaBridge extends BridgeAbstract
{
    const NAME = 'Philomena';
    const URI = '';
    const DESCRIPTION = 'Base bridge for Philomena-based imageboards (use a site-specific bridge instead)';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 1800;

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
                'title' => 'Tags for exclude, separated by commas or spaces (e.g., "tag1, tag2" or "tag1 tag2"). Posts containing ANY of these tags will be excluded from the feed'
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
                'name' => 'Hide tags and sources',
                'type' => 'checkbox',
                'required' => false,
                'defaultValue' => 'checked'
            ]
        ]
    ];

    protected static function getAvailableFilters(): array
    {
        return [];
    }

    protected static function getDefaultFilterId(): int
    {
        return 0;
    }

    public function getParameters(): array
    {
        $params = parent::getParameters();
        $filters = static::getAvailableFilters();
        if (!empty($filters)) {
            $params['Global']['f'] = [
                'name' => 'Content Filter',
                'type' => 'list',
                'values' => $filters,
                'defaultValue' => static::getDefaultFilterId()
            ];
        }
        return $params;
    }

    public function detectParameters($url)
    {
        if (get_class($this) === self::class) {
            return null;
        }

        $host = parse_url(static::URI, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        $escapedHost = preg_quote($host, '/');
        $params = [];

        $regex = '/^(https?:\/\/)?(www\.)?' . $escapedHost . '\/search(?:\?.*)?/';
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

        $regex = '/^(https?:\/\/)?(www\.)?' . $escapedHost . '\/tags\/([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['q'] = str_replace('-colon-', ':', urldecode($matches[3]));
            return $params;
        }

        return null;
    }

    private function getNormalizedQuery(): string
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

    public function getName(): string
    {
        $q = $this->getNormalizedQuery();
        if (!empty($q)) {
            return static::NAME . ': ' . $q;
        }
        return parent::getName();
    }

    public function getURI(): string
    {
        $q = $this->getNormalizedQuery();
        if (!empty($q)) {
            $url = static::URI . 'search?';
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

    public function collectData(): void
    {
        if (get_class($this) === self::class) {
            throwClientException(
                'This is a base bridge class for Philomena-based imageboards. '
                . 'Please use a site-specific bridge instead (e.g., DerpibooruBridge or TrixiebooruBridge).'
            );
        }

        $q = $this->getNormalizedQuery();

        if (empty($q)) {
            throwClientException('Query cannot be empty.');
        }

        $f = $this->getInput('f') ?? static::getDefaultFilterId();
        $sf = $this->getInput('sf') ?? 'created_at';
        $sd = $this->getInput('sd') ?? 'desc';
        $limit = $this->getInput('limit') ?? 10;
        $hideTags = $this->getInput('hide_tags') ?? false;

        $limit = min(50, max(1, (int) $limit));

        $apiUrl = $this->buildApiUrl($q, (int) $f, $sf, $sd, $limit);

        try {
            $jsonString = getContents($apiUrl);
            $json = json_decode($jsonString, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throwClientException('Failed to parse API response: ' . $e->getMessage());
        }

        if (!$json || !isset($json->images)) {
            throwClientException('Invalid API response.');
        }

        if (empty($json->images)) {
            return;
        }

        foreach ($json->images as $post) {
            $postUri = static::URI . 'images/' . $post->id;
            $artist = $this->extractArtist($post->tags ?? []);

            $title = !empty($artist)
                ? sprintf('Image %s by %s', $post->id, $artist)
                : sprintf('Image %s', $post->id);

            $this->items[] = [
                'uri' => $postUri,
                'title' => $title,
                'timestamp' => strtotime($post->created_at),
                'author' => $post->uploader ?? 'Anonymous',
                'content' => $this->buildPostHtml($post, $postUri, (bool) $hideTags),
            ];
        }
    }

    private function buildApiUrl(string $query, int $filter, string $sf, string $sd, int $limit): string
    {
        return sprintf(
            '%sapi/v1/json/search/images?filter_id=%s&q=%s&sf=%s&sd=%s&per_page=%d',
            static::URI,
            urlencode((string) $filter),
            urlencode($query),
            urlencode($sf),
            urlencode($sd),
            $limit
        );
    }

    private function extractArtist(array $tags): string
    {
        foreach ($tags as $tag) {
            if (strpos($tag, 'artist:') === 0) {
                return substr($tag, 7);
            }
        }
        return '';
    }

    private function buildPostHtml(object $post, string $postUri, bool $hideTags): string
    {
        $html = '';

        $isVideo = (isset($post->mime_type) && strpos($post->mime_type, 'video/') === 0)
            || (isset($post->format) && in_array($post->format, ['webm', 'mp4'], true));

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

        if (!$hideTags) {
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

            if (!empty($post->tags) && is_array($post->tags)) {
                $html .= sprintf(
                    '<p><b>Tags:</b> %s</p>',
                    htmlspecialchars(implode(', ', $post->tags))
                );
            }
        }

        return $html;
    }

    private function cleanDescription(string $text): string
    {
        $patterns = [
            '/\[([^\]]+)\]\([^)]+\)/' => '$1',
            '/!\[([^\]]*)\]\([^)]+\)/' => '',
            '/^#+\s+/m' => '',
            '/^>\s+/m' => '',
            '/```([^`]+)```/' => '$1',
            '/`([^`]+)`/' => '$1',
            '/^[-*_]{3,}$/m' => '',
        ];

        $text = preg_replace(array_keys($patterns), array_values($patterns), $text);
        $text = str_replace(['*', '\\'], '', $text);
        $text = preg_replace('/[^\x00-\x7F\x{0400}-\x{04FF}\p{P}\s]/u', '', $text);
        $text = preg_replace(['/\n{3,}/', '/ {2,}/'], ["\n\n", ' '], $text);

        return trim($text);
    }
}