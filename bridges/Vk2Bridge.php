<?php

declare(strict_types=1);

class Vk2Bridge extends BridgeAbstract
{
    const NAME = 'VK';
    const URI = 'https://vk.ru';
    const DESCRIPTION = 'Returns posts from the public feed. Needs personal API key';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 900;

    // No magic in the Muggle world 
    private const VK_API_VER = 5.199;
    private const VK_API_MAX_COUNT = 100;
    private const DEFAULT_POST_LIMIT = 20;
    private const API_DELAY_US = 400000;
    private const MAX_TITLE_LENGTH = 50;
    private const MAX_PREVIEW_LENGTH = 200;
    private const MIN_TITLE_SPACE_POS = 25;

    private const URL_REGEX = '/^https?:\/\/(?:www\.|m\.)?vk\.ru\/([a-zA-Z0-9_.]+)\/?$/i';

    private const SYSTEM_PAGES = [
        'video', 'audio', 'photos', 'messages', 'feed', 'friends', 'groups', 'settings',
        'login', 'reg', 'restore', 'im', 'mail', 'news', 'search', 'apps', 'games',
        'gifts', 'support', 'write', 'wall', 'board', 'albums', 'docs', 'topics',
        'public', 'event', 'market', 'contacts', 'about', 'reviews', 'edit',
    ];

    private const VALID_FILTERS = ['all', 'owner', 'others'];

    private const ERROR_MESSAGES = [
        'owner_not_found' => 'Could not detect owner id. Please check if the short name is correct and the page is not blocked or deleted',
        'invalid_api_response' => 'Invalid API response from',
        'missing_access_token' => 'You cannot run VK API methods without access_token',
        'invalid_json' => 'Invalid JSON response from VK API',
        'api_error' => 'API returned error:',
        'no_posts_found' => 'Feed is empty:',
        'captcha_needed' => 'Captcha required:',
        'access_denied' => 'Access denied. The wall or group might be private.',
        'user_deleted' => 'User was deleted or banned.',
        'unknown_method' => 'Unknown API method. The API version might be outdated.',
    ];

    const PARAMETERS = [
        [
            'u' => [
                'name' => 'Name of group or profile',
                'type' => 'text',
                'required' => true,
                'title' => 'Name from URL. Example: rebel_jack from https://vk.ru/rebel_jack',
            ],
            'filter' => [
                'name' => 'Filter posts',
                'type' => 'list',
                'defaultValue' => 'all',
                'values' => [
                    'All posts' => 'all',
                    'Owner posts' => 'owner',
                    'Reposts' => 'others',
                ],
            ],
            'limit' => [
                'name' => 'Number of posts',
                'type' => 'number',
                'defaultValue' => self::DEFAULT_POST_LIMIT,
            ],
        ],
    ];

    const CONFIGURATION = [
        'access_token' => [
            'required' => true,
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://vk.ru/rebel_jack' => ['u' => 'rebel_jack'],
    ];

    const RATE_LIMIT_ERRORS = [
        6 => 15,
        29 => 1800,
    ];

    protected array $ownerNames = [];
    protected ?string $pageName = null;
    protected ?string $iconUrl = null;
    protected array $photoDescriptions = [];

    public function getURI(): string
    {
        $u = $this->getInput('u');
        return !empty($u) ? static::URI . '/' . $u : parent::getURI();
    }

    public function getName(): string
    {
        return $this->pageName ?? parent::getName();
    }

    public function getIcon(): string
    {
        return $this->iconUrl ? $this->proxyImage($this->iconUrl) : parent::getIcon();
    }

    public function detectParameters($url): ?array
    {
        if (preg_match(self::URL_REGEX, $url, $m)) {
            $name = strtolower($m[1]);
            if (in_array($name, self::SYSTEM_PAGES, true)) {
                return null;
            }
            return ['u' => $m[1]];
        }
        return null;
    }

    public function collectData(): void
    {
        if ($this->cache->get($this->getRateLimitCacheKey())) {
            throwRateLimitException();
        }

        $ownerId = $this->detectOwnerId($this->getInput('u'));
        usleep(self::API_DELAY_US);

        $targetCount = max(1, min(self::VK_API_MAX_COUNT, (int)($this->getInput('limit') ?: self::DEFAULT_POST_LIMIT)));
        $filter = $this->getInput('filter') ?? 'all';
        if (!in_array($filter, self::VALID_FILTERS, true)) {
            $filter = 'all';
        }

        $fetchCounts = [$targetCount * 2, $targetCount * 4];
        $filteredPosts = [];
        $lastFetchedCount = 0;

        foreach ($fetchCounts as $fetchCount) {
            $apiCount = min($fetchCount, self::VK_API_MAX_COUNT);

            if ($apiCount <= $lastFetchedCount) {
                break;
            }

            $r = $this->api('wall.get', [
                'owner_id' => $ownerId,
                'extended' => '1',
                'count' => $apiCount,
                'filter' => $filter,
            ]);

            if (!isset($r['response']['items'])) {
                $this->handleError('invalid_api_response', 'wall.get');
            }

            $this->cacheOwnerData($r['response'], $ownerId);

            foreach ($r['response']['items'] as $post) {
                if (($post['marked_as_ads'] ?? 0) === 1 || ($post['is_pinned'] ?? 0) === 1) {
                    continue;
                }
                $filteredPosts[] = $post;
            }

            $lastFetchedCount = $apiCount;

            if (count($filteredPosts) >= $targetCount) {
                break;
            }

            usleep(self::API_DELAY_US);
        }

        if (empty($filteredPosts)) {
            $this->handleError('no_posts_found', 'No posts found in the feed with the selected filter.');
        }

        $filteredPosts = array_slice($filteredPosts, 0, $targetCount);
        $this->generateFeed($filteredPosts, $ownerId);
    }

    protected function getPostURI(array $post): string
    {
        $ownerId = $post['owner_id'] ?? 0;
        $id = $post['id'] ?? 0;
        $r = 'https://vk.ru/wall' . $ownerId . '_' . $id;

        if (isset($post['reply_post_id'])) {
            $threadId = $post['parents_stack'][0] ?? $post['reply_post_id'];
            $r .= '?reply=' . $id . '&thread=' . $threadId;
        }

        return $r;
    }

    protected function generateContentFromPost(array $post, bool $extractTitle = true): string
    {
        $text = $post['text'] ?? '';

        if ($extractTitle) {
            [, $text] = $this->splitFirstLine($text);
        }

        $text = trim($text);
        $ret = '';

        if ($text !== '') {
            $ret = nl2br($this->e($text));

            $ret = preg_replace_callback(
                '~(https?://[^\s<|]+)|((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?:/[^\s<|]*)?)~i',
                function ($m) {
                    if (!empty($m[1])) {
                        return $this->safeLink($m[1], $m[1]);
                    }
                    if (!empty($m[2])) {
                        return $this->safeLink('https://' . $m[2], $m[2]);
                    }
                    return $m[0];
                },
                $ret
            );

            $ret = preg_replace_callback(
                '/\[([a-zA-Z0-9_\-\.\/:]+)\|([^\]]+)\]/i',
                function ($m) {
                    $target = $m[1];
                    $text = $m[2];
                    
                    if (preg_match('/^https?:\/\//i', $target)) {
                        return $this->safeLink($target, $text);
                    }
                    
                    if (preg_match('/^(id|club|public|wall|post|event|market)([\-0-9_]+)$/i', $target, $tm)) {
                        $prefix = strtolower($tm[1]);
                        $id = $tm[2];
                        return $this->safeLink("https://vk.ru/{$prefix}{$id}", $text);
                    }
                    
                    return $this->safeLink('https://vk.ru/' . $target, $text);
                },
                $ret
            );

            $ret = preg_replace_callback(
                '/#([a-zA-Zà-ÿÀ-ß¸¨0-9_]+(?:@[a-zA-Z0-9_.]+)?)/u',
                function ($m) {
                    $tag = $m[1];
                    return $this->safeLink('https://vk.ru/feed?q=%23' . urlencode($tag), '#' . $tag);
                },
                $ret
            );

            $ret = "<p>{$ret}</p>";
        }

        foreach ($post['attachments'] ?? [] as $attachment) {
            $ret .= $this->renderAttachment($attachment);
        }

        return $ret;
    }

    protected function renderAttachment(array $attachment): string
    {
        $type = $attachment['type'] ?? '';
        $data = $attachment[$type] ?? [];

        switch ($type) {
            case 'photo':
                $key = ($data['owner_id'] ?? 0) . '_' . ($data['id'] ?? 0);
                $text = $data['text'] ?? ($this->photoDescriptions[$key] ?? '');
                $url = $this->getLargestImageUrl($data['sizes'] ?? []);
                return "<p>{$this->image($url, $text)}</p>";
            case 'video':
                return $this->renderVideo($data);
            case 'audio':
                return $this->renderAudio($data);
            case 'doc':
                return $this->renderDoc($data);
            case 'link':
                return $this->renderLink($data);
            case 'note':
                $url = $data['view_url'] ?? '#';
                $title = $data['title'] ?? 'Note';
                return "<p>{$this->link($url, $title)}</p>";
            case 'poll':
                return $this->renderPoll($data);
            case 'album':
                return $this->renderAlbum($data);
            case 'article':
                return $this->renderArticle($data);
            case 'wall':
                return $this->renderWall($data);
            case 'market':
                return $this->renderMarket($data);
            case 'audio_playlist':
                return $this->renderAudioPlaylist($data);
            case 'sticker':
                return $this->renderSticker($data);
            default:
                return "<p>Unknown attachment type: {$this->e($type)}</p>";
        }
    }

    protected function getTitle(array $post): string
    {
        [$title] = $this->splitFirstLine($post['text'] ?? '');

        if ($title !== '') {
            return $title;
        }

        foreach ($post['attachments'] ?? [] as $attachment) {
            $t = $this->getAttachmentTitle($attachment);
            if ($t !== '') {
                return $t;
            }
        }

        return 'untitled';
    }

    protected function api(string $method, array $params, array $expectedErrorCodes = []): array
    {
        $accessToken = $this->getOption('access_token');

        if (!$accessToken) {
            $this->handleError('missing_access_token');
        }

        $params['v'] = self::VK_API_VER;
        $url = 'https://api.vk.ru/method/' . $method . '?' . http_build_query($params);

        try {
            $response = getContents($url, ['Authorization: Bearer ' . $accessToken]);
        } catch (\Exception $e) {
            $this->handleError('api_error', 'Network error: ' . $e->getMessage());
        }

        $r = json_decode($response, true);

        if (!is_array($r)) {
            $this->handleError('invalid_json');
        }

        if (!isset($r['error'])) {
            return $r;
        }

        $errorCode = $r['error']['error_code'] ?? 0;

        if ($errorCode === 14) {
            $this->cache->set($this->getRateLimitCacheKey(), true, 300);
            $this->handleError('captcha_needed', $r['error']['error_msg'] ?? 'Captcha needed');
        }

        if ($errorCode === 15) {
            $this->handleError('access_denied', $r['error']['error_msg'] ?? 'Access denied');
        }

        if ($errorCode === 18) {
            $this->handleError('user_deleted', $r['error']['error_msg'] ?? 'User was deleted or banned');
        }

        if ($errorCode === 3) {
            $this->handleError('unknown_method', $r['error']['error_msg'] ?? 'Unknown API method');
        }

        if (in_array($errorCode, $expectedErrorCodes, true)) {
            return $r;
        }

        if (isset(self::RATE_LIMIT_ERRORS[$errorCode])) {
            $this->cache->set($this->getRateLimitCacheKey(), true, self::RATE_LIMIT_ERRORS[$errorCode]);
        }

        $errorMsg = $r['error']['error_msg'] ?? 'Unknown error';
        $this->handleError('api_error', "{$errorMsg} ({$errorCode})");
    }

    private function generateFeed(array $posts, int $ownerId): void
    {
        $this->fetchPhotoDescriptions($posts);

        foreach ($posts as $post) {
            $isRepost = $this->isRepost($post);
            $displayPost = $isRepost ? $post['copy_history'][0] : $post;

            $uri = $this->getPostURI($displayPost);
            $cacheKey = 'vk2_post_' . md5($uri);
            $cachedItem = $this->cache->get($cacheKey);

            if ($cachedItem !== null) {
                $this->items[] = $cachedItem;
                continue;
            }

            $fromId = $displayPost['from_id'] ?? $displayPost['owner_id'] ?? 0;
            $author = ($fromId !== $ownerId) ? ($this->ownerNames[$fromId] ?? 'Unknown') : '';

            $item = [
                'content'   => $this->generateContentFromPost($displayPost, true),
                'timestamp' => $displayPost['date'] ?? time(),
                'author'    => $author,
                'title'     => $this->getTitle($displayPost),
                'uri'       => $uri,
            ];

            $this->cache->set($cacheKey, $item, self::CACHE_TIMEOUT);
            $this->items[] = $item;
        }

        if (isset($this->ownerNames[$ownerId])) {
            $this->pageName = $this->ownerNames[$ownerId];
        }
    }

    private function fetchPhotoDescriptions(array $posts): void
    {
        $photoKeys = [];
        foreach ($posts as $post) {
            foreach ($post['attachments'] ?? [] as $attachment) {
                if (($attachment['type'] ?? '') === 'photo') {
                    $photo = $attachment['photo'] ?? [];
                    $ownerId = $photo['owner_id'] ?? 0;
                    $id = $photo['id'] ?? 0;
                    if ($ownerId !== 0 && $id !== 0) {
                        $photoKeys[] = $ownerId . '_' . $id;
                    }
                }
            }
        }

        if (empty($photoKeys)) {
            return;
        }

        $photoKeys = array_unique($photoKeys);
        $r = $this->api('photos.getById', ['photos' => implode(',', $photoKeys)], [100, 20, 15]);

        if (isset($r['response']) && is_array($r['response'])) {
            foreach ($r['response'] as $photo) {
                $key = ($photo['owner_id'] ?? 0) . '_' . ($photo['id'] ?? 0);
                $this->photoDescriptions[$key] = $photo['text'] ?? '';
            }
        }
    }

    private function cacheOwnerData(array $response, int $ownerId): void
    {
        foreach ($response['profiles'] ?? [] as $profile) {
            $this->ownerNames[$profile['id']] = trim(
                ($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')
            );

            if ($profile['id'] === $ownerId) {
                $newIcon = $profile['photo_100'] ?? $profile['photo_50'] ?? null;
                if ($newIcon !== null) {
                    $this->iconUrl = $newIcon;
                }
            }
        }

        foreach ($response['groups'] ?? [] as $group) {
            $id = -(int)$group['id'];
            $this->ownerNames[$id] = $group['name'] ?? 'Unknown';

            if ($id === $ownerId) {
                $newIcon = $group['photo_200'] ?? $group['photo_100'] ?? $group['photo_50'] ?? null;
                if ($newIcon !== null) {
                    $this->iconUrl = $newIcon;
                }
            }
        }
    }

    private function isRepost(array $post): bool
    {
        return isset($post['copy_history'][0]);
    }

    private function detectOwnerId(string $u): int
    {
        if (preg_match('/^(club|public)(\d+)$/', $u, $matches)) {
            return -intval($matches[2]);
        }

        if (preg_match('/^id(\d+)$/', $u, $matches)) {
            return intval($matches[1]);
        }

        $cacheKey = 'vk2_owner_' . md5(strtolower($u));
        $cached = $this->cache->get($cacheKey);

        if (is_array($cached) && isset($cached['ownerId'])) {
            $this->iconUrl = $cached['iconUrl'] ?? null;
            return (int)$cached['ownerId'];
        }

        $r = $this->api('groups.getById', ['group_ids' => $u, 'fields' => 'photo_200,photo_100,photo_50'], [100]);

        if (isset($r['response']['groups'][0]['id'])) {
            $group = $r['response']['groups'][0];
            $ownerId = -$group['id'];
            $iconUrl = $group['photo_200'] ?? $group['photo_100'] ?? $group['photo_50'] ?? null;

            $this->iconUrl = $iconUrl;
            $this->cache->set($cacheKey, ['ownerId' => $ownerId, 'iconUrl' => $iconUrl], 86400);
            return $ownerId;
        }

        if (isset($r['response'][0]['id'])) {
            $group = $r['response'][0];
            $ownerId = -$group['id'];
            $iconUrl = $group['photo_200'] ?? $group['photo_100'] ?? $group['photo_50'] ?? null;

            $this->iconUrl = $iconUrl;
            $this->cache->set($cacheKey, ['ownerId' => $ownerId, 'iconUrl' => $iconUrl], 86400);
            return $ownerId;
        }

        usleep(self::API_DELAY_US);
        $r = $this->api('users.get', ['user_ids' => $u, 'fields' => 'photo_100,photo_50']);

        if (isset($r['response'][0]['id'])) {
            $user = $r['response'][0];
            $ownerId = $user['id'];
            $iconUrl = $user['photo_100'] ?? $user['photo_50'] ?? null;

            $this->iconUrl = $iconUrl;
            $this->cache->set($cacheKey, ['ownerId' => $ownerId, 'iconUrl' => $iconUrl], 86400);
            return $ownerId;
        }

        $this->handleError('owner_not_found', "Short name '{$u}'");
    }

    private function renderVideo(array $data): string
    {
        $title = $data['title'] ?? 'Video';
        $isStory = ($data['type'] ?? '') === 'story';
        $prefix = $isStory ? 'clip' : 'video';
        
        $width = $data['width'] ?? 0;
        $height = $data['height'] ?? 0;
        $resolution = ($width > 0 && $height > 0) ? " ({$width}x{$height})" : '';
        $duration = $data['duration'] ?? 0;

        if ($isStory) {
            $title = "Clip: {$title}" . ($duration > 0 ? " ({$duration} sec)" : '');
        } else {
            $durationStr = $duration > 0 ? ' (' . gmdate('i:s', $duration) . ')' : '';
            $title = "Video: {$title}{$resolution}{$durationStr}";
        }

        $ownerId = $data['owner_id'] ?? 0;
        $id = $data['id'] ?? 0;
        $url = "https://vk.ru/{$prefix}{$ownerId}_{$id}";
        $img = $this->getLargestImageUrl($data['image'] ?? []);
        $eTitle = $this->e($title);

        return "<p>{$this->linkHtml($url, $this->image($img, $title) . "<br/>{$eTitle}")}</p>";
    }

    private function renderAudio(array $data): string
    {
        $artist = $this->e($data['artist'] ?? '');
        $title = $this->e($data['title'] ?? '');
        $duration = $data['duration'] ?? 0;
        $durationStr = $duration > 0 ? ' (' . gmdate('i:s', $duration) . ')' : '';
        
        return "<p>Audio: {$artist} - {$title}{$durationStr}</p>";
    }

    private function renderDoc(array $data): string
    {
        $url = $data['url'] ?? '#';
        $title = $data['title'] ?? 'Document';

        if (($data['ext'] ?? '') === 'gif') {
            return "<p>{$this->image($this->proxyImage($url), $title)}</p>";
        }

        return "<p>{$this->link($url, "Document: {$title}")}</p>";
    }

    private function renderLink(array $data): string
    {
        $url = str_replace('https://m.vk.ru', 'https://vk.ru', $data['url'] ?? '#');
        $title = $data['title'] ?? $url;

        if (isset($data['photo']['sizes'])) {
            $img = $this->getLargestImageUrl($data['photo']['sizes']);
            $eTitle = $this->e($title);
            return "<p>{$this->linkHtml($url, $this->image($img, $title) . "<br>{$eTitle}")}</p>";
        }

        return "<p>{$this->link($url, $title)}</p>";
    }

    private function renderPoll(array $data): string
    {
        $question = $this->e($data['question'] ?? 'Poll');
        $votes = $data['votes'] ?? 0;
        $html = "<p>Poll: {$question} ({$votes} votes)<br />";

        foreach ($data['answers'] ?? [] as $answer) {
            $text = $this->e($answer['text'] ?? '');
            $aVotes = $answer['votes'] ?? 0;
            $rate = $answer['rate'] ?? 0;
            $html .= "* {$text}: {$aVotes} ({$rate}%)<br />";
        }

        return $html . '</p>';
    }

    private function renderAlbum(array $data): string
    {
        $ownerId = $data['owner_id'] ?? 0;
        $id = $data['id'] ?? 0;
        $url = "https://vk.ru/album{$ownerId}_{$id}";
        $title = 'Album: ' . ($data['title'] ?? '');
        $img = $this->getLargestImageUrl($data['thumb']['sizes'] ?? []);
        $eTitle = $this->e($title);

        return "<p>{$this->linkHtml($url, $this->image($img, $title) . "<br>{$eTitle}")}</p>";
    }

    private function renderArticle(array $data): string
    {
        $url = $data['view_url'] ?? '#';
        $title = $data['title'] ?? 'Article';
        $img = $this->getLargestImageUrl($data['photo']['sizes'] ?? []);
        $eTitle = $this->e($title);
        
        return "<p>{$this->linkHtml($url, $this->image($img, $title) . "<br>{$eTitle}")}</p>";
    }

    private function renderWall(array $data): string
    {
        $url = $this->getPostURI($data);
        $text = $data['text'] ?? '';
        $preview = mb_substr($text, 0, self::MAX_PREVIEW_LENGTH);
        
        if (mb_strlen($text) > self::MAX_PREVIEW_LENGTH) {
            $preview .= '...';
        }
        
        $ePreview = $this->e($preview);
        
        return "<p><strong>Attached post:</strong><br>{$this->linkHtml($url, $ePreview)}</p>";
    }

    private function renderMarket(array $data): string
    {
        $url = $data['url'] ?? '#';
        $title = $data['title'] ?? 'Product';
        $price = $data['price']['text'] ?? '';
        $img = $data['thumb_photo'] ?? '';
        
        $displayText = $title;
        if ($price !== '') {
            $displayText .= " — {$price}";
        }
        
        $eDisplayText = $this->e($displayText);
        
        if ($img !== '') {
            return "<p>{$this->linkHtml($url, $this->image($this->proxyImage($img), $title) . "<br>{$eDisplayText}")}</p>";
        }
        
        return "<p>{$this->link($url, $displayText)}</p>";
    }

    private function renderAudioPlaylist(array $data): string
    {
        $ownerId = $data['owner_id'] ?? 0;
        $id = $data['id'] ?? 0;
        $url = "https://vk.ru/music/playlist/{$ownerId}_{$id}";
        $title = $data['title'] ?? 'Playlist';
        $count = $data['count'] ?? 0;
        
        $displayText = "Playlist: {$title} ({$count} tracks)";
        
        return "<p>{$this->link($url, $displayText)}</p>";
    }

    private function renderSticker(array $data): string
    {
        $images = $data['images'] ?? [];
        
        if (empty($images)) {
            return '';
        }
        
        usort($images, fn($a, $b) => ($b['width'] ?? 0) <=> ($a['width'] ?? 0));
        $imgUrl = $images[0]['url'] ?? '';
        
        if (empty($imgUrl)) {
            return '';
        }
        
        return "<p>{$this->image($this->proxyImage($imgUrl), 'Sticker')}</p>";
    }

    private function getAttachmentTitle(array $attachment): string
    {
        $type = $attachment['type'] ?? '';
        $data = $attachment[$type] ?? [];

        switch ($type) {
            case 'video':
                $prefix = ($data['type'] ?? '') === 'story' ? 'Clip: ' : 'Video: ';
                return $prefix . ($data['title'] ?? '');
            case 'audio':
                return 'Audio: ' . ($data['artist'] ?? '') . ' - ' . ($data['title'] ?? '');
            case 'link':
                return 'Link: ' . ($data['title'] ?? '');
            case 'doc':
                return 'Document: ' . ($data['title'] ?? '');
            case 'album':
                return 'Album: ' . ($data['title'] ?? '');
            case 'poll':
                return 'Poll: ' . ($data['question'] ?? '');
            case 'photo':
                return 'Photo';
            case 'article':
                return 'Article: ' . ($data['title'] ?? '');
            case 'wall':
                return 'Attached post';
            case 'market':
                return 'Product: ' . ($data['title'] ?? '');
            case 'audio_playlist':
                return 'Playlist: ' . ($data['title'] ?? '');
            case 'sticker':
                return 'Sticker';
            default:
                return '';
        }
    }

    private function splitFirstLine(string $text): array
    {
        $lines = explode("\n", $text);
        $meaningfulIndex = null;

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if ($trimmed !== '' && !$this->isLinkOnly($trimmed)) {
                $meaningfulIndex = $i;
                break;
            }
        }

        if ($meaningfulIndex === null) {
            return ['', $text];
        }

        $originalLine = $lines[$meaningfulIndex];
        $cleanLine = preg_replace(
            '/\[(?:id|club|public)?[a-zA-Z0-9_.]+\|([^\]]+)\]/i',
            '$1',
            trim($originalLine)
        );

        if (empty(trim($cleanLine))) {
            return ['', $text];
        }

        if (mb_strlen($cleanLine) <= self::MAX_TITLE_LENGTH) {
            unset($lines[$meaningfulIndex]);
            return [$cleanLine, implode("\n", array_values($lines))];
        }

        $cut = mb_substr($cleanLine, 0, self::MAX_TITLE_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');
        $cutPos = ($lastSpace !== false && $lastSpace > self::MIN_TITLE_SPACE_POS) ? $lastSpace : self::MAX_TITLE_LENGTH;

        $title = mb_substr($cleanLine, 0, $cutPos) . '...';
        $remainder = mb_substr($originalLine, $cutPos);

        $lines[$meaningfulIndex] = '...' . $remainder;

        return [$title, implode("\n", $lines)];
    }

    private function isLinkOnly(string $line): bool
    {
        $line = trim($line);

        if ($line === '') {
            return false;
        }

        return (bool)preg_match(
            '~^(https?://[^\s]+|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:/[^\s]*)?)$~i',
            $line
        );
    }

    private function getLargestImageUrl(array $sizes): string
    {
        if (empty($sizes)) {
            return '';
        }

        usort($sizes, fn($a, $b) => ($b['width'] ?? 0) <=> ($a['width'] ?? 0));

        return $this->proxyImage($sizes[0]['url'] ?? '');
    }

    private function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function safeLink(string $url, string $text = ''): string
    {
        $url = trim($url);
        if ($url === '') {
            return $this->e($text);
        }
        
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return $this->e($text !== '' ? $text : $url);
        }

        $safeUrl = $this->e($url);
        $safeText = $this->e($text !== '' ? $text : $url);
        
        return "<a href='{$safeUrl}' target='_blank' rel='noopener noreferrer'>{$safeText}</a>";
    }

    private function link(string $url, string $text): string
    {
        return $this->linkHtml($url, $this->e($text));
    }

    private function linkHtml(string $url, string $html): string
    {
        $url = $this->e($url);
        return "<a href='{$url}' target='_blank' rel='noopener noreferrer'>{$html}</a>";
    }

    private function image(string $url, string $alt): string
    {
        $url = $this->e($url);
        $alt = $this->e($alt);
        return "<img src='{$url}' alt='{$alt}'>";
    }

    private function proxyImage(string $url): string
    {
        return $url;
    }

    private function getRateLimitCacheKey(): string
    {
        $token = $this->getOption('access_token') ?? '';
        return 'vk2_rate_limit_' . md5($token);
    }

    private function handleError(string $code, string $details = ''): void
    {
        $message = self::ERROR_MESSAGES[$code] ?? 'Unknown error';

        if ($details !== '') {
            $message .= ' ' . $details;
        }

        returnServerError($message);
    }
}