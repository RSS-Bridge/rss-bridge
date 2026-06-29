<?php

declare(strict_types=1);

class FimfictionBridge extends BridgeAbstract {
    const MAINTAINER = 'LordArrin';
    const NAME = 'Fimfiction Updates';
    const URI = 'https://www.fimfiction.net/';
    const DESCRIPTION = 'Returns chapter updates for stories on Fimfiction using FlareSolverr to bypass Cloudflare';
    const CACHE_TIMEOUT = 3600;

    const CONFIGURATION = [
        'flaresolverr_url' => ['required' => true],
        'session_token' => ['required' => false],
        'signing_key' => ['required' => false],
    ];

    const PARAMETERS = [
        [
            'story_id' => [
                'name' => 'Story ID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '550684'
            ],
            'full_content' => [
                'name' => 'Fetch full chapter content',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
        ]
    ];

    const SESSION_NAME = 'fimfiction';
    const FETCH_LIMIT = 3;
    const FLARESOLVERR_TIMEOUT = 60000;
    const STORY_CACHE_TTL = 900;
    const CHAPTER_CACHE_TTL = 86400;
    const MAX_RETRY_ATTEMPTS = 3;
    const RETRY_BASE_DELAY = 2;
    const FLARESOLVERR_PAGE_WAIT = 2000;
    const STORY_PAGE_MAX_ATTEMPTS = 3;
    const STORY_PAGE_RETRY_DELAY = 2;

    private const CSS = [
        'wrapper'       => 'font-size:14px; line-height:1.6; word-wrap:break-word;',
        'scene-break'   => 'text-align:center; color:#888; margin:1.5em 0; font-size:1.2em; letter-spacing:0.5em;',
        'chapter-link'  => 'margin:0.5em 0;',
        'error'         => 'color:#b00; font-style:italic;',
    ];

    private ?string $storyTitle = null;
    private ?string $storyImage = null;
    private ?string $flaresolverrUrl = null;

    public function getName(): string {
        return $this->storyTitle ?? parent::getName();
    }

    public function getURI(): string {
        $id = $this->getInput('story_id');
        return $id ? self::URI . 'story/' . $id . '/' : self::URI;
    }

    public function getIcon(): string {
        return $this->storyImage ?? parent::getIcon();
    }

    public function collectData(): void {
        $storyId = $this->getInput('story_id');
        if (!$storyId) {
            returnClientError('Story ID is required');
        }

        $fullContent = (bool)$this->getInput('full_content');
        $fetchFromPages = $fullContent;

        $this->flaresolverrUrl = $this->getConfigValue('flaresolverr_url');
        if (!$this->flaresolverrUrl) {
            returnClientError('FlareSolverr URL is required in config.ini.php [FimfictionBridge] section');
        }

        if (!$this->ensureSession()) {
            returnClientError('Failed to establish FlareSolverr session. Check FlareSolverr service and configuration.');
        }

        $cacheKey = 'story_page_' . $storyId;
        $htmlString = $this->loadCacheValue($cacheKey);

        if ($htmlString && !$this->validateStoryPage($htmlString)) {
            $this->deleteCacheValue($cacheKey);
            $htmlString = null;
        }

        if (!$htmlString) {
            for ($attempt = 1; $attempt <= self::STORY_PAGE_MAX_ATTEMPTS; $attempt++) {
                $rawHtml = $this->fetchHtml($this->getStoryUrl($storyId));

                if ($this->validateStoryPage($rawHtml)) {
                    $htmlString = $rawHtml;
                    break;
                }

                if ($attempt < self::STORY_PAGE_MAX_ATTEMPTS) {
                    sleep(self::STORY_PAGE_RETRY_DELAY * $attempt);
                }
            }

            if (!$htmlString) {
                returnClientError('Received invalid story page after ' . self::STORY_PAGE_MAX_ATTEMPTS . ' attempts (possibly Cloudflare challenge or mature content). Try again later.');
            }

            $this->saveCacheValue($cacheKey, $htmlString, self::STORY_CACHE_TTL);
        }

        $dom = str_get_html($htmlString);

        if (!$dom) {
            returnClientError('Failed to parse story page HTML');
        }

        $storyError = $this->detectStoryError($dom);
        if ($storyError) {
            returnClientError($storyError);
        }

        $this->storyTitle = $this->extractStoryTitle($dom);
        $this->storyImage = $this->extractStoryImage($dom);
        $author = $this->extractAuthor($dom);

        $chaptersData = $this->extractChaptersList($dom, self::FETCH_LIMIT);

        foreach ($chaptersData as $data) {
            $content = $fetchFromPages
                ? $this->buildFullContent($data['uri'], $fullContent)
                : $this->buildLinkContent($data['uri']);

            $this->items[] = [
                'title' => $data['title'],
                'uri' => $data['uri'],
                'author' => $author,
                'content' => $content,
                'timestamp' => $data['timestamp'],
                'uid' => $data['uri'],
            ];
        }
    }

    private function getStyle(string $key, string ...$args): string {
        $style = self::CSS[$key] ?? '';
        return $args ? sprintf($style, ...$args) : $style;
    }

    private function getStoryUrl(string $storyId): string {
        return self::URI . 'story/' . $storyId . '/';
    }

    private function getConfigValue(string $key): ?string {
        $value = $this->getOption($key);
        return ($value !== null && $value !== '') ? trim((string)$value, " \t\n\r\0\x0B\"'") : null;
    }

    private function ensureSession(): bool {
        $existingSessions = $this->flaresolverrRequest([
            'cmd' => 'sessions.list',
        ], true);

        $sessionExists = false;
        if ($existingSessions && isset($existingSessions['sessions'])) {
            foreach ($existingSessions['sessions'] as $session) {
                if (isset($session['session']) && $session['session'] === self::SESSION_NAME) {
                    $sessionExists = true;
                    break;
                }
            }
        }

        if (!$sessionExists) {
            $cookies = [];
            $sessionToken = $this->getConfigValue('session_token');
            $signingKey = $this->getConfigValue('signing_key');

            if ($sessionToken && $signingKey) {
                $cookies = [
                    ['name' => 'session_token', 'value' => $sessionToken, 'domain' => 'www.fimfiction.net'],
                    ['name' => 'signing_key', 'value' => $signingKey, 'domain' => 'www.fimfiction.net'],
                    ['name' => 'view_mature', 'value' => 'true', 'domain' => 'www.fimfiction.net'],
                ];
            }

            $result = $this->flaresolverrRequest([
                'cmd' => 'sessions.create',
                'session' => self::SESSION_NAME,
                'maxTimeout' => self::FLARESOLVERR_TIMEOUT,
                'cookies' => $cookies
            ], true);

            if (!$result || ($result['status'] ?? '') !== 'ok') {
                return false;
            }
        }

        return true;
    }

    private function flaresolverrRequest(array $postData, bool $ignoreErrors = false): array {
        $attempt = 0;
        $lastError = '';

        while ($attempt < self::MAX_RETRY_ATTEMPTS) {
            $ch = curl_init(rtrim($this->flaresolverrUrl, '/') . '/v1');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 120,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $lastError = 'FlareSolverr connection error: ' . $curlError;
                if (!$ignoreErrors) {
                    $attempt++;
                    if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                        $delay = self::RETRY_BASE_DELAY * (2 ** ($attempt - 1));
                        sleep($delay);
                        continue;
                    }
                    returnClientError($lastError);
                }
                return [];
            }

            if ($httpCode !== 200) {
                $lastError = 'FlareSolverr returned HTTP ' . $httpCode;
                if (!$ignoreErrors) {
                    $attempt++;
                    if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                        $delay = self::RETRY_BASE_DELAY * (2 ** ($attempt - 1));
                        sleep($delay);
                        continue;
                    }
                    returnClientError($lastError);
                }
                return [];
            }

            $result = json_decode((string)$response, true);

            if (!$result || ($result['status'] !== 'ok' && !$ignoreErrors)) {
                $lastError = 'FlareSolverr failed: ' . ($result['message'] ?? 'Unknown error');
                if (!$ignoreErrors) {
                    $attempt++;
                    if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                        $delay = self::RETRY_BASE_DELAY * (2 ** ($attempt - 1));
                        sleep($delay);
                        continue;
                    }
                    returnClientError($lastError);
                }
                return $result ?? [];
            }

            return $result ?? [];
        }

        if (!$ignoreErrors && $lastError) {
            returnClientError('FlareSolverr failed after ' . self::MAX_RETRY_ATTEMPTS . ' attempts: ' . $lastError);
        }

        return [];
    }

    private function fetchHtml(string $url): string {
        $cookies = [];
        $sessionToken = $this->getConfigValue('session_token');
        $signingKey = $this->getConfigValue('signing_key');

        if ($sessionToken && $signingKey) {
            $cookies = [
                ['name' => 'session_token', 'value' => $sessionToken, 'domain' => 'www.fimfiction.net'],
                ['name' => 'signing_key', 'value' => $signingKey, 'domain' => 'www.fimfiction.net'],
            ];
        }

        $cookies[] = ['name' => 'view_mature', 'value' => 'true', 'domain' => 'www.fimfiction.net'];

        $result = $this->flaresolverrRequest([
            'cmd' => 'request.get',
            'url' => $url,
            'session' => self::SESSION_NAME,
            'maxTimeout' => self::FLARESOLVERR_TIMEOUT,
            'wait' => self::FLARESOLVERR_PAGE_WAIT,
            'cookies' => $cookies,
        ]);
        return (string)$result['solution']['response'];
    }

    private function validateStoryPage(string $html): bool {
        $challengeMarkers = [
            'Checking your browser',
            'cf-browser-verification',
            'challenge-platform',
            'Just a moment...',
            'Enable JavaScript',
        ];

        foreach ($challengeMarkers as $marker) {
            if (stripos($html, $marker) !== false) {
                return false;
            }
        }

        $requiredElements = [
            '<ul class="chapters"',
            'class="story_name"',
            'class="story_container"',
        ];

        foreach ($requiredElements as $element) {
            if (stripos($html, $element) === false) {
                return false;
            }
        }

        return true;
    }

    private function deleteCacheValue(string $key): void {
        $this->saveCacheValue($key, '', 0);
    }

    private function detectStoryError(\simple_html_dom $dom): ?string {
        $errorMessages = [
            'Story not found' => 'This story has been deleted or does not exist.',
            'This story is not available' => 'This story is not available in your region.',
            'You must be logged in' => 'This story requires login to view.',
            'Mature Content' => 'This story contains mature content and requires authentication.',
        ];

        foreach ($errorMessages as $pattern => $message) {
            if (stripos($dom->plaintext, $pattern) !== false) {
                return $message;
            }
        }

        $storyName = $dom->find('a.story_name', 0);
        $chapters = $dom->find('ul.chapters', 0);

        if (!$storyName && !$chapters) {
            return 'Story page loaded but no story content found. The story may be private or restricted.';
        }

        return null;
    }

    private function extractStoryTitle(\simple_html_dom $dom): string {
        $titleElem = $dom->find('a.story_name', 0);
        return $titleElem
            ? trim($titleElem->plaintext)
            : trim(str_replace(' - Fimfiction', '', $dom->find('title', 0)->plaintext ?? 'Unknown Story'));
    }

    private function extractStoryImage(\simple_html_dom $dom): ?string {
        $container = $dom->find('[class*=story_container__story_image]', 0);
        if (!$container) {
            return null;
        }

        $img = $container->find('img', 0);
        return ($img && $img->src) ? $img->src : null;
    }

    private function extractAuthor(\simple_html_dom $dom): string {
        $authorElem = $dom->find('div.info-container a[href*=/user/]', 0) ?? $dom->find('a[href*=/user/]', 0);
        return $authorElem ? trim($authorElem->plaintext) : 'Unknown';
    }

    private function extractChaptersList(\simple_html_dom $dom, int $limit): array {
        $chapterList = $dom->find('ul.chapters', 0);
        if (!$chapterList) {
            returnClientError('Could not find chapter list (ul.chapters)');
        }

        $chapters = [];
        $index = 1;

        foreach ($chapterList->find('li') as $chapter) {
            $titleBox = $chapter->find('.title-box', 0);
            $link = $titleBox ? $titleBox->find('a.chapter-title', 0) : null;

            if (!$link) {
                $index++;
                continue;
            }

            $uri = $link->href;
            if (strpos($uri, 'http') !== 0) {
                $uri = self::URI . ltrim($uri, '/');
            }

            $chapters[] = [
                'title' => trim($link->plaintext),
                'uri' => $uri,
                'timestamp' => $this->extractTimestamp($chapter) + $index,
            ];
            $index++;
        }

        usort($chapters, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($chapters, 0, $limit);
    }

    private function extractTimestamp($chapterElem): int {
        $dateElem = $chapterElem ? $chapterElem->find('.title-box .date', 0) : null;

        if (!$dateElem) {
            return time();
        }

        $fullText = $dateElem->plaintext;

        if (preg_match('/(\d{1,2})(?:st|nd|rd|th)\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})/', $fullText, $matches)) {
            $timestamp = strtotime("{$matches[1]} {$matches[2]} {$matches[3]}");
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return time();
    }

    private function buildFullContent(string $uri, bool $fullContent): string {
        $cacheKey = 'chapter_' . md5($uri . ($fullContent ? '_full' : '_link'));

        $cached = $this->loadCacheValue($cacheKey);
        if ($cached !== null && $cached !== '') {
            return $cached;
        }

        $html = $this->fetchHtml($uri);
        $dom = str_get_html($html);

        if (!$dom) {
            return '<p style="' . $this->getStyle('error') . '">Error loading chapter content.</p>';
        }

        $content = '<div style="' . $this->getStyle('wrapper') . '">';

        if ($fullContent) {
            $body = $dom->find('#chapter-body .bbcode', 0);
            if ($body) {
                $this->sanitizeContent($body);
                $this->styleSceneBreaks($body);
                $content .= $body->innertext;
            } else {
                $content .= '<p style="' . $this->getStyle('error') . '">Chapter content could not be loaded.</p>';
            }
        } else {
            $safeUri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
            $content .= '<p style="' . $this->getStyle('chapter-link') . '">New chapter published - <a href="' . $safeUri . '">read full</a></p>';
        }

        $content .= '</div>';

        $this->saveCacheValue($cacheKey, $content, self::CHAPTER_CACHE_TTL);

        return $content;
    }

    private function buildLinkContent(string $uri): string {
        $safeUri = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        return '<div style="' . $this->getStyle('wrapper') . '">'
             . '<p style="' . $this->getStyle('chapter-link') . '">New chapter published - <a href="' . $safeUri . '">read full</a></p>'
             . '</div>';
    }

    private function sanitizeContent(\simple_html_dom_node $element): void {
        $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select'];
        foreach ($dangerousTags as $tag) {
            foreach ($element->find($tag) as $dangerous) {
                $dangerous->outertext = '';
            }
        }

        foreach ($element->find('*') as $node) {
            $attributes = $node->getAllAttributes();
            if ($attributes) {
                foreach (array_keys($attributes) as $attr) {
                    if (stripos($attr, 'on') === 0) {
                        $node->removeAttribute($attr);
                    }
                    if (in_array(strtolower($attr), ['href', 'src'])) {
                        $value = $node->getAttribute($attr);
                        if ($value && stripos(trim($value), 'javascript:') === 0) {
                            $node->removeAttribute($attr);
                        }
                    }
                }
            }
        }
    }

    private function styleSceneBreaks(\simple_html_dom_node $element): void {
        foreach ($element->find('hr') as $hr) {
            $hr->outertext = '<p style="' . $this->getStyle('scene-break') . '">• • •</p>';
        }
    }
}