<?php
class TrixiebooruBridge extends BridgeAbstract {
    const NAME = 'Trixiebooru Bridge';
    const URI = 'https://trixiebooru.org/';
    const DESCRIPTION = 'Returns images and videos from trixiebooru search';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 1800; // 30 minutes

    const PARAMETERS = array(
        'Global' => array(
            'q' => array(
                'name' => 'Query (Tags)',
                'required' => true,
                'exampleValue' => 'pin-up, princess_luna',
                'title' => 'Search query (tags, artist, etc.)'
            ),
            'exclude_tags' => array(
                'name' => 'Blacklist (Exclude tags)',
                'required' => false,
                'exampleValue' => 'g5',
                'title' => 'Comma-separated list of tags to hide. Posts containing ANY of these tags will be excluded from the feed.'
            ),
            'f' => array(
                'name' => 'Content Filter',
                'type' => 'list',
                'values' => array(
                    'Everything (No limits, shows ALL)' => 56027,
                    '18+ R34 (Explicit allowed, hides gore/AI)' => 37432,
                    '18+ Dark (Gore/grimdark allowed, hides explicit)' => 37429,
                    'Legacy Default (Old safe mode, hides explicit)' => 37431,
                    'Default (Modern safe, hides non-art & adult)' => 100073
                ),
                'defaultValue' => 56027
            ),
            'sf' => array(
                'name' => 'Sort By',
                'type' => 'list',
                'values' => array(
                    'Creation date' => 'created_at',
                    'Score' => 'score',
                    'Wilson score' => 'wilson_score',
                    'Favorites' => 'faves',
                    'Upvotes' => 'upvotes',
                    'Views' => 'views',
                    'Comments' => 'comments',
                    'Random' => 'random'
                ),
                'defaultValue' => 'created_at'
            ),
            'sd' => array(
                'name' => 'Sort Direction',
                'type' => 'list',
                'values' => array(
                    'Descending' => 'desc',
                    'Ascending' => 'asc'
                ),
                'defaultValue' => 'desc'
            ),
            'limit' => array(
                'name' => 'Posts Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of posts to fetch (API allows up to 50)',
                'defaultValue' => 10,
                'exampleValue' => 10
            ),
            'hide_tags' => array(
                'name' => 'Hide tags',
                'type' => 'checkbox',
                'title' => 'Check this box to completely hide the tags list from the post content',
                'defaultValue' => false
            )
        )
    );

    public function detectParameters($url) {
        $params = array();
        
        // Handle search page URL: https://trixiebooru.org/search?q=[tag]%2C+[tag]&sf=score&sd=desc
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
        
        // Handle tag page URL: https://trixiebooru.org/tags/artist-colon-[name]
        $regex = '/^(https?:\/\/)?(www\.)?trixiebooru\.org\/tags\/([^\/&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['q'] = str_replace('-colon-', ':', urldecode($matches[3]));
            return $params;
        }
        
        return null;
    }

    public function getName() {
        if (!is_null($this->getInput('q'))) {
            return self::NAME . ': ' . $this->getInput('q');
        }
        return parent::getName();
    }

    public function getURI() {
        if (!is_null($this->getInput('q'))) {
            $url = self::URI . 'search?';
            $params = array(
                'q' => $this->getInput('q'),
                'sf' => $this->getInput('sf') ?? 'created_at',
                'sd' => $this->getInput('sd') ?? 'desc'
            );
            if (!is_null($this->getInput('f'))) {
                $params['filter_id'] = $this->getInput('f');
            }
            return $url . http_build_query($params);
        }
        return parent::getURI();
    }

    public function collectData() {
        $q = $this->getInput('q') ?? '';
        $excludeTags = $this->getInput('exclude_tags') ?? '';
        $f = $this->getInput('f') ?? 56027;
        $sf = $this->getInput('sf') ?? 'created_at';
        $sd = $this->getInput('sd') ?? 'desc';
        $limit = $this->getInput('limit') ?? 20;
        $hideTags = $this->getInput('hide_tags') ?? false;
        
        // Process excluded tags (Blacklist)
        if (!empty(trim($excludeTags))) {
            $excludes = array_map('trim', explode(',', $excludeTags));
            $excludeQuery = '';
            foreach ($excludes as $tag) {
                if (!empty($tag)) {
                    // Remove existing minus if user accidentally typed it, to avoid double minus
                    $cleanTag = ltrim($tag, '-');
                    // trixiebooru uses comma for AND, so we append ", -tag"
                    $excludeQuery .= ', -' . $cleanTag;
                }
            }
            $q .= $excludeQuery;
        }
        
        // trixiebooru API strictly limits per_page to a maximum of 50
        $limit = min(50, max(1, (int)$limit));

        $query = urlencode($q);
        $filter = urlencode($f);
        
        $apiUrl = self::URI . "api/v1/json/search/images?filter_id={$filter}&q={$query}&sf={$sf}&sd={$sd}&per_page={$limit}";
        
        // Fetch JSON data
        $jsonString = getContents($apiUrl);
        $json = json_decode($jsonString);
        
        if (!$json || !isset($json->images)) {
            returnClientError('No images found or invalid API response.');
        }
        
        foreach ($json->images as $post) {
            $item = array();
            
            $postUri = self::URI . 'images/' . $post->id;
            $item['uri'] = $postUri;
            
            // Use ID and first few tags for a compact title
            $tagsSlice = array_slice($post->tags, 0, 5);
            $item['title'] = 'Image ' . $post->id . ' (' . implode(', ', $tagsSlice) . ')';
            
            $item['timestamp'] = strtotime($post->created_at);
            $item['author'] = $post->uploader ?? 'Anonymous';
            
            // Build HTML content
            $html = '';
            
            // Check if the post is a video (webm/mp4)
            $isVideo = false;
            if (isset($post->mime_type) && strpos($post->mime_type, 'video/') === 0) {
                $isVideo = true;
            } elseif (isset($post->format) && in_array($post->format, ['webm', 'mp4'])) {
                $isVideo = true;
            }
            
            $mediaUrl = $post->representations->full ?? '';
            $thumbUrl = $post->representations->medium ?? $post->representations->small ?? $mediaUrl;
            
            if ($isVideo && !empty($mediaUrl)) {
                // Embed HTML5 video player directly in content
                $html .= '<p><a href="' . $postUri . '"><video controls loop muted preload="metadata" style="max-width:100%; height:auto;" src="' . htmlspecialchars($mediaUrl) . '"></video></a></p>';
            } elseif (!empty($thumbUrl)) {
                $html .= '<p><a href="' . $postUri . '"><img src="' . htmlspecialchars($thumbUrl) . '" alt="Image ' . $post->id . '"></a></p>';
            }
            
            if (!empty($post->description)) {
                $cleanDesc = $this->cleanDescription($post->description);
                if (!empty($cleanDesc)) {
                    $html .= '<p><b>Description:</b><br>' . nl2br(htmlspecialchars($cleanDesc)) . '</p>';
                }
            }
            
            $html .= '<p><b>Size:</b> ' . ($post->width ?? '?') . 'x' . ($post->height ?? '?') . ' | <b>Score:</b> ' . ($post->score ?? 'N/A') . '</p>';
            
            // Handle source_urls array safely
            if (!empty($post->source_urls)) {
                $sources = '';
                foreach ($post->source_urls as $source) {
                    $sources .= '<a href="' . htmlspecialchars($source) . '" rel="noopener noreferrer">' . htmlspecialchars($source) . '</a><br>';
                }
                $html .= '<p><b>Sources:</b><br>' . $sources . '</p>';
            }
            
            // List all tags in a single line, comma-separated (only if not hidden)
            if (!$hideTags && !empty($post->tags) && is_array($post->tags)) {
                $html .= '<p><b>Tags:</b> ' . htmlspecialchars(implode(', ', $post->tags)) . '</p>';
            }
            
            $item['content'] = $html;
            
            $this->items[] = $item;
        }
    }

    private function cleanDescription($text) {
        // Remove markdown links, keep only the text: [text](url) → text
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        
        // Remove markdown images: ![alt](url) → (empty)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);
        
        // Remove ALL asterisks (bold/italic markers)
        $text = str_replace('*', '', $text);
        
        // Remove ALL backslashes
        $text = str_replace('\\', '', $text);
        
        // Remove markdown headers: # Header → Header
        $text = preg_replace('/^#+\s+/m', '', $text);
        
        // Remove markdown blockquotes: > text → text
        $text = preg_replace('/^>\s+/m', '', $text);
        
        // Remove markdown code blocks: ```code``` → code, `code` → code
        $text = preg_replace('/```([^`]+)```/', '$1', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        
        // Remove markdown horizontal rules: --- or *** or ___
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text);
        
        // Remove emojis and exotic Unicode characters (keep basic Latin, Cyrillic, numbers, punctuation)
        $text = preg_replace('/[^\x00-\x7F\x{0400}-\x{04FF}\p{P}\s]/u', '', $text);
        
        // Clean up excessive whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/ {2,}/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
}