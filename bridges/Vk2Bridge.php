<?php

declare(strict_types=1);

class Vk2Bridge extends BridgeAbstract
{
    const NAME = 'VK';
    const URI = 'https://vk.ru';
    const DESCRIPTION = 'Returns posts from the public feed. Needs personal API key';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 900;

    private const VK_API_VER = 5.199;
    private const VK_API_MAX_COUNT = 100;
    private const DEFAULT_POST_LIMIT = 20;
    private const MAX_TITLE_LENGTH = 60;
    private const MAX_PREVIEW_LENGTH = 200;
    private const MIN_TITLE_SPACE_POS = 30;
    private const MAX_PAGES = 5;
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_US = 500_000;

    private const URL_REGEX = '/^https?:\/\/(?:www\.|m\.)?vk\.ru\/([a-zA-Z0-9_.]+)\/?$/i';

    private const SYSTEM_PAGES = [
        'video', 'audio', 'photos', 'messages', 'feed', 'friends', 'groups', 'settings',
        'login', 'reg', 'restore', 'im', 'mail', 'news', 'search', 'apps', 'games',
        'gifts', 'support', 'write', 'wall', 'board', 'albums', 'docs', 'topics',
        'public', 'event', 'market', 'contacts', 'about', 'reviews', 'edit',
    ];

    private const ERROR_MESSAGES = [
        'owner_not_found' => 'Could not detect owner id. Check the short name.',
        'invalid_api_response' => 'Invalid API response from',
        'missing_access_token' => 'Access token is required.',
        'invalid_json' => 'Invalid JSON response from VK API.',
        'api_error' => 'API returned error:',
        'no_posts_found' => 'Feed is empty:',
        'captcha_needed' => 'Captcha required:',
        'access_denied' => 'Access denied. The wall or group might be private.',
        'user_deleted' => 'User was deleted or banned.',
        'unknown_method' => 'Unknown API method.',
    ];

    private const RATE_LIMIT_ERRORS = [
        6 => 15,
        29 => 1800,
    ];

    const PARAMETERS = [
        [
            'u' => [
                'name' => 'Name of group or profile',
                'type' => 'text',
                'required' => true,
                'title' => 'Name from URL. Example: rebel_jack from https://vk.ru/rebel_jack',
            ],
            'hide_reposts' => [
                'name' => 'Hide reposts',
                'type' => 'checkbox',
                'title' => 'Check this box to hide reposts from feed items',
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
        $targetCount = max(1, min(self::VK_API_MAX_COUNT, (int)($this->getInput('limit') ?: self::DEFAULT_POST_LIMIT)));
        $hideReposts = (bool)$this->getInput('hide_reposts');
        $filteredPosts = [];
        $offset = 0;

        for ($page = 0; $page < self::MAX_PAGES && count($filteredPosts) < $targetCount; $page++) {
            $batchSize = min(self::VK_API_MAX_COUNT, max($targetCount - count($filteredPosts) + 10, 10));

            $r = $this->api('wall.get', [
                'owner_id' => $ownerId,
                'extended' => '1',
                'count' => $batchSize,
                'offset' => $offset,
                'fields' => 'photo_200,photo_100,photo_50',
            ]);

            if (!isset($r['response']['items'])) {
                $this->handleError('invalid_api_response', 'wall.get');
            }

            $this->cacheOwnerData($r['response'], $ownerId);
            $items = $r['response']['items'];

            if (empty($items)) {
                break;
            }

            foreach ($items as $post) {
                if (($post['marked_as_ads'] ?? 0) === 1 || ($post['is_pinned'] ?? 0) === 1) {
                    continue;
                }
                if ($hideReposts && $this->isRepost($post)) {
                    continue;
                }
                if (($post['is_deleted'] ?? false) === true
                    && trim($post['text'] ?? '') === ''
                    && empty($post['attachments'])
                ) {
                    continue;
                }
                $filteredPosts[] = $post;
            }

            $offset += count($items);

            if (count($items) < $batchSize) {
                break;
            }
        }

        if (empty($filteredPosts)) {
            $reason = $hideReposts
                ? 'No original posts found after filtering reposts.'
                : 'No posts found in the feed.';
            $this->handleError('no_posts_found', $reason);
        }

        $this->generateFeed(array_slice($filteredPosts, 0, $targetCount), $ownerId);
    }

    protected function getPostURI(array $post): string
    {
        $r = 'https://vk.ru/wall' . ($post['owner_id'] ?? 0) . '_' . ($post['id'] ?? 0);
        if (isset($post['reply_post_id'])) {
            $threadId = $post['parents_stack'][0] ?? $post['reply_post_id'];
            $r .= '?reply=' . ($post['id'] ?? 0) . '&thread=' . $threadId;
        }
        return $r;
    }

    protected function generateContentFromPost(array $post, bool $extractTitle = true): string
    {
        $text = $post['text'] ?? '';

        if ($extractTitle) {
            [, $text] = $this->splitFirstLine($text);
        }

        $text = trim($text ?? '');
        $ret = '';

        if ($text !== '') {
            $placeholders = [];
            $counter = 0;

            $text = $this->applyPlaceholders($text, $placeholders, $counter,
                '/\[([^\]|]+)\|([^\]]+)\]/u',
                function (array $m): string {
                    return $this->resolveVkLink(trim($m[1]), $m[2]);
                }
            );

            $text = $this->applyPlaceholders($text, $placeholders, $counter,
                '/#([\p{L}0-9_]+(?:@[\p{L}0-9_.]+)?)/u',
                function (array $m): string {
                    return $this->safeLink('https://vk.ru/feed?q=%23' . urlencode($m[1]), '#' . $m[1]);
                }
            );

            $text = $this->applyPlaceholders($text, $placeholders, $counter,
                '~(https?://[^\s<|]+)|((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?:/[^\s<|]*)?)~i',
                function (array $m): ?string {
                    if (!empty($m[1])) {
                        return $this->safeLink($m[1], $m[1]);
                    }
                    if (!empty($m[2])) {
                        return $this->safeLink('https://' . $m[2], $m[2]);
                    }
                    return null;
                }
            );

            $ret = $this->e($text);
            if (!empty($placeholders)) {
                $ret = str_replace(array_keys($placeholders), array_values($placeholders), $ret);
            }
            $ret = '<p>' . nl2br($ret) . '</p>';
        }

        foreach ($post['attachments'] ?? [] as $attachment) {
            $ret .= $this->renderAttachment($attachment);
        }

        return $ret;
    }

    protected function renderAttachment(array $attachment): string
    {
        $type = $attachment['type'] ?? '';
        $d = $attachment[$type] ?? [];

        switch ($type) {
            case 'photo':
                $key = ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
                $alt = $d['text'] ?? ($this->photoDescriptions[$key] ?? '');
                return "<p>{$this->image($this->getLargestImageUrl($d['sizes'] ?? []), $alt)}</p>";

            case 'video':
                return $this->renderVideo($d);

            case 'clip':
                return $this->renderClip($d);

            case 'audio':
                return $this->renderAudio($d);

            case 'doc':
                if (($d['ext'] ?? '') === 'gif') {
                    return "<p>{$this->image($this->proxyImage($d['url'] ?? ''), $d['title'] ?? 'Document')}</p>";
                }
                return "<p>{$this->link($d['url'] ?? '#', 'Document: ' . ($d['title'] ?? 'Document'))}</p>";

            case 'link':
                $url = str_replace('https://m.vk.ru', 'https://vk.ru', $d['url'] ?? '#');
                $normalized = $this->normalizePlaylistUrl($url);
                $isPlaylist = $normalized !== $url;
                $url = $normalized;
                $img = (!$isPlaylist && isset($d['photo']['sizes'])) ? $this->getLargestImageUrl($d['photo']['sizes']) : '';
                $title = $isPlaylist ? 'Playlist: ' . ($d['title'] ?? $url) : ($d['title'] ?? $url);
                return $this->renderLinkCard($url, $title, $img);

            case 'note':
                return $this->renderLinkCard($d['view_url'] ?? '#', $d['title'] ?? 'Note');

            case 'poll':
                return $this->renderPoll($d);

            case 'album':
                $url = 'https://vk.ru/album' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
                return $this->renderLinkCard($url, 'Album: ' . ($d['title'] ?? ''), $this->getLargestImageUrl($d['thumb']['sizes'] ?? []));

            case 'article':
                return $this->renderLinkCard($d['view_url'] ?? '#', $d['title'] ?? 'Article', $this->getLargestImageUrl($d['photo']['sizes'] ?? []));

            case 'wall':
                return $this->renderWall($d);

            case 'market':
                $price = $d['price']['text'] ?? '';
                $display = $price !== '' ? ($d['title'] ?? 'Product') . ' - ' . $price : ($d['title'] ?? 'Product');
                $img = ($d['thumb_photo'] ?? '') !== '' ? $this->proxyImage($d['thumb_photo']) : '';
                return $this->renderLinkCard($d['url'] ?? '#', $display, $img);

            case 'audio_playlist':
                $url = 'https://vk.ru/music/playlist/' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
                $title = 'Playlist: ' . ($d['title'] ?? '') . ' (' . ($d['count'] ?? 0) . ' tracks)';
                return $this->renderLinkCard($url, $title);

            case 'video_playlist':
                $url = 'https://vk.ru/video/playlist/' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
                return $this->renderLinkCard($url, 'Video playlist: ' . ($d['title'] ?? '') . ' (' . ($d['count'] ?? 0) . ')', $this->getLargestImageUrl($d['photo'] ?? []));

            case 'podcast':
                return $this->renderPodcast($d);

            case 'event':
                return $this->renderEvent($d);

            case 'graffiti':
                $url = $d['photo_586'] ?? $d['photo_200'] ?? '';
                return $url !== '' ? "<p>{$this->image($this->proxyImage($url), 'Graffiti')}</p>" : '';

            case 'group':
                $url = ($d['screen_name'] ?? '') !== '' ? 'https://vk.ru/' . $d['screen_name'] : '#';
                $img = $d['photo_200'] ?? $d['photo_100'] ?? $d['photo_50'] ?? '';
                return $this->renderLinkCard($url, $d['name'] ?? 'Group', $img !== '' ? $this->proxyImage($img) : '');

            case 'donut_link':
                return $this->renderLinkCard($d['url'] ?? '#', $d['text'] ?? 'VK Donut');

            case 'textlive':
            case 'textpost':
            case 'textpost_publish':
                $preview = mb_substr($d['text'] ?? '', 0, self::MAX_PREVIEW_LENGTH);
                $extra = $preview !== '' ? "<p><small>{$this->e($preview)}</small></p>" : '';
                return $this->renderLinkCard($d['url'] ?? '#', $d['title'] ?? 'Text broadcast', '', $extra);

            case 'situational_theme':
                return $this->renderLinkCard($d['url'] ?? '#', $d['title'] ?? '');

            case 'sticker':
                return $this->renderSticker($d);

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
        $retryDelayUs = self::RETRY_DELAY_US;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = getContents($url, ['Authorization: Bearer ' . $accessToken]);
            } catch (\Exception $e) {
                if ($attempt < self::MAX_RETRIES) {
                    usleep($retryDelayUs);
                    $retryDelayUs *= 2;
                    continue;
                }
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

            if ($errorCode === 6 && $attempt < self::MAX_RETRIES) {
                usleep($retryDelayUs);
                $retryDelayUs *= 2;
                continue;
            }

            if ($errorCode === 14) {
                $this->cache->set($this->getRateLimitCacheKey(), true, 300);
                $this->handleError('captcha_needed', $r['error']['error_msg'] ?? 'Captcha needed');
            }

            if ($errorCode === 15) {
                $this->handleError('access_denied', $r['error']['error_msg'] ?? 'Access denied');
            }

            if ($errorCode === 18) {
                $this->handleError('user_deleted', $r['error']['error_msg'] ?? 'User deleted or banned');
            }

            if ($errorCode === 3) {
                $this->handleError('unknown_method', $r['error']['error_msg'] ?? 'Unknown method');
            }

            if (in_array($errorCode, $expectedErrorCodes, true)) {
                return $r;
            }

            if (isset(self::RATE_LIMIT_ERRORS[$errorCode])) {
                $this->cache->set($this->getRateLimitCacheKey(), true, self::RATE_LIMIT_ERRORS[$errorCode]);
            }

            $this->handleError('api_error', ($r['error']['error_msg'] ?? 'Unknown error') . " ({$errorCode})");
        }

        throw new \RuntimeException('VK API: max retries exceeded for ' . $method);
    }

    private function generateFeed(array $posts, int $ownerId): void
    {
        $this->fetchPhotoDescriptions($posts);

        foreach ($posts as $post) {
            $displayPost = $this->isRepost($post) ? $post['copy_history'][0] : $post;
            $uri = $this->getPostURI($displayPost);

            $cacheKey = 'vk2_post_' . md5(
                $uri . '|' . ($displayPost['date'] ?? '') . '|' . ($displayPost['edited'] ?? '')
            );
            $cachedItem = $this->cache->get($cacheKey);

            if ($cachedItem !== null) {
                $this->items[] = $cachedItem;
                continue;
            }

            $fromId = $displayPost['from_id'] ?? $displayPost['owner_id'] ?? 0;
            $author = ($fromId !== $ownerId) ? ($this->ownerNames[$fromId] ?? 'Unknown') : '';
            $isDeleted = ($displayPost['is_deleted'] ?? false) === true;

            $content = $this->generateContentFromPost($displayPost, true);
            if ($isDeleted) {
                $content = '<p><em>[Deleted]</em></p>' . $content;
            }

            $item = [
                'content' => $content,
                'timestamp' => $displayPost['date'] ?? time(),
                'author' => $author,
                'title' => ($isDeleted ? '[Deleted] ' : '') . $this->getTitle($displayPost),
                'uri' => $uri,
                'uid' => 'vk:wall' . ($displayPost['owner_id'] ?? 0) . '_' . ($displayPost['id'] ?? 0),
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
                if (($attachment['type'] ?? '') !== 'photo') {
                    continue;
                }
                $p = $attachment['photo'] ?? [];
                $oid = $p['owner_id'] ?? 0;
                $pid = $p['id'] ?? 0;
                if ($oid !== 0 && $pid !== 0) {
                    $photoKeys[] = $oid . '_' . $pid;
                }
            }
        }

        if (empty($photoKeys)) {
            return;
        }

        $r = $this->api('photos.getById', ['photos' => implode(',', array_unique($photoKeys))], [100, 20, 15]);

        foreach ($r['response'] ?? [] as $photo) {
            $key = ($photo['owner_id'] ?? 0) . '_' . ($photo['id'] ?? 0);
            $this->photoDescriptions[$key] = $photo['text'] ?? '';
        }
    }

    private function cacheOwnerData(array $response, int $ownerId): void
    {
        foreach ($response['profiles'] ?? [] as $profile) {
            $this->ownerNames[$profile['id']] = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
            if ($profile['id'] === $ownerId) {
                $this->iconUrl = $profile['photo_100'] ?? $profile['photo_50'] ?? $this->iconUrl;
            }
        }

        foreach ($response['groups'] ?? [] as $group) {
            $id = -(int)$group['id'];
            $this->ownerNames[$id] = $group['name'] ?? 'Unknown';
            if ($id === $ownerId) {
                $this->iconUrl = $group['photo_200'] ?? $group['photo_100'] ?? $group['photo_50'] ?? $this->iconUrl;
            }
        }
    }

    private function isRepost(array $post): bool
    {
        return isset($post['copy_history'][0]);
    }

    private function detectOwnerId(string $u): int
    {
        if (preg_match('/^(club|public)(\d+)$/', $u, $m)) {
            return -intval($m[2]);
        }

        if (preg_match('/^id(\d+)$/', $u, $m)) {
            return intval($m[1]);
        }

        $cacheKey = 'vk2_owner_' . md5(strtolower($u));
        $cached = $this->cache->get($cacheKey);

        if (is_array($cached) && isset($cached['ownerId'])) {
            $this->iconUrl = $cached['iconUrl'] ?? null;
            return (int)$cached['ownerId'];
        }

        $r = $this->api('groups.getById', ['group_ids' => $u, 'fields' => 'photo_200,photo_100,photo_50'], [100]);
        $group = $r['response']['groups'][0] ?? $r['response'][0] ?? null;

        if ($group !== null && isset($group['id'])) {
            $ownerId = -(int)$group['id'];
            $this->iconUrl = $group['photo_200'] ?? $group['photo_100'] ?? $group['photo_50'] ?? null;
            $this->cache->set($cacheKey, ['ownerId' => $ownerId, 'iconUrl' => $this->iconUrl], 86400);
            return $ownerId;
        }

        $r = $this->api('users.get', ['user_ids' => $u, 'fields' => 'photo_100,photo_50']);

        if (isset($r['response'][0]['id'])) {
            $user = $r['response'][0];
            $this->iconUrl = $user['photo_100'] ?? $user['photo_50'] ?? null;
            $this->cache->set($cacheKey, ['ownerId' => $user['id'], 'iconUrl' => $this->iconUrl], 86400);
            return $user['id'];
        }

        $this->handleError('owner_not_found', "Short name '{$u}'");
    }

    private function renderVideo(array $d): string
    {
        $title = $d['title'] ?? 'Video';

        if (mb_stripos($title, 'Клип ') === 0) {
            return $this->renderClip($d);
        }

        $isLive = ($d['live'] ?? 0) === 1;
        $duration = $d['duration'] ?? 0;
        $dur = $duration > 0 ? ' (' . gmdate('i:s', $duration) . ')' : '';
        $w = $d['width'] ?? 0;
        $h = $d['height'] ?? 0;
        $res = ($w > 0 && $h > 0) ? " ({$w}x{$h})" : '';

        if ($isLive) {
            $labels = ['waiting' => 'Scheduled', 'started' => 'LIVE', 'finished' => 'Ended', 'failed' => 'Failed', 'upcoming' => 'Upcoming'];
            $status = $labels[$d['live_status'] ?? ''] ?? ($d['live_status'] ?? 'unknown');
            $title = "[{$status}] {$title}";
            if (($d['spectators'] ?? 0) > 0) {
                $title .= " ({$d['spectators']} viewers)";
            }
        } else {
            $title = "Video: {$title}{$dur}{$res}";
        }

        $url = 'https://vk.ru/video' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
        return $this->renderLinkCard($url, $title, $this->getLargestImageUrl($d['image'] ?? []));
    }

    private function renderClip(array $d): string
    {
        $title = $this->cleanClipTitle($d['title'] ?? 'Clip');
        $duration = $d['duration'] ?? 0;
        $dur = $duration > 0 ? ' (' . gmdate('i:s', $duration) . ')' : '';
        $w = $d['width'] ?? 0;
        $h = $d['height'] ?? 0;
        $res = ($w > 0 && $h > 0) ? " ({$w}x{$h})" : '';
        $title = "Clip: {$title}{$dur}{$res}";
        $url = 'https://vk.ru/clip' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
        return $this->renderLinkCard($url, $title, $this->getLargestImageUrl($d['image'] ?? []));
    }

    private function renderAudio(array $d): string
    {
        $url = 'https://vk.ru/audio' . ($d['owner_id'] ?? 0) . '_' . ($d['id'] ?? 0);
        $dur = ($d['duration'] ?? 0) > 0 ? ' (' . gmdate('i:s', $d['duration']) . ')' : '';
        return $this->renderLinkCard($url, 'Music: ' . ($d['artist'] ?? '') . ' - ' . ($d['title'] ?? '') . $dur);
    }

    private function renderLinkCard(string $url, string $title, string $img = '', string $extra = ''): string
    {
        $eTitle = $this->e($title);
        $inner = $img !== '' ? $this->image($img, $title) . "<br>{$eTitle}" : $eTitle;
        $html = "<p>{$this->linkHtml($url, $inner)}</p>";
        return $extra !== '' ? $html . $extra : $html;
    }

    private function renderPoll(array $d): string
    {
        $html = '<p>Poll: ' . $this->e($d['question'] ?? 'Poll') . ' (' . ($d['votes'] ?? 0) . " votes)<br>";
        foreach ($d['answers'] ?? [] as $a) {
            $html .= '* ' . $this->e($a['text'] ?? '') . ': ' . ($a['votes'] ?? 0) . ' (' . ($a['rate'] ?? 0) . "%)<br>";
        }
        return $html . '</p>';
    }

    private function renderWall(array $d): string
    {
        $text = $d['text'] ?? '';
        $preview = mb_substr($text, 0, self::MAX_PREVIEW_LENGTH);
        if (mb_strlen($text) > self::MAX_PREVIEW_LENGTH) {
            $preview .= '...';
        }
        return '<p><strong>Attached post:</strong><br>' . $this->linkHtml($this->getPostURI($d), $this->e($preview)) . '</p>';
    }

    private function renderSticker(array $d): string
    {
        $images = $d['images'] ?? [];
        if (empty($images)) {
            return '';
        }
        usort($images, fn($a, $b) => ($b['width'] ?? 0) <=> ($a['width'] ?? 0));
        $url = $images[0]['url'] ?? '';
        return $url !== '' ? "<p>{$this->image($this->proxyImage($url), 'Sticker')}</p>" : '';
    }

    private function renderEvent(array $d): string
    {
        $time = $d['time'] ?? 0;
        $date = $d['date'] ?? 0;
        $timeStr = $time > 0 ? date('Y-m-d H:i', $time) : ($date > 0 ? date('Y-m-d', $date) : '');
        $html = '<p>Event: ' . $this->e($d['text'] ?? 'Event');
        if ($timeStr !== '') {
            $html .= "<br><small>{$this->e($timeStr)}</small>";
        }
        if (($d['address'] ?? '') !== '') {
            $html .= '<br><small>Location: ' . $this->e($d['address']) . '</small>';
        }
        return $html . '</p>';
    }

    private function renderPodcast(array $d): string
    {
        $title = $d['podcast_title'] ?? $d['title'] ?? 'Podcast';
        $artist = $d['artist'] ?? '';
        $url = $d['url'] ?? '#';
        $display = $artist !== '' ? "Podcast: {$title} - {$artist}" : "Podcast: {$title}";
        $cover = '';

        foreach ($d['podcast_cover'] ?? [] as $img) {
            if (isset($img['url'])) {
                $cover = $img['url'];
            }
        }

        $html = '';
        if ($cover !== '') {
            $html .= "<p>{$this->linkHtml($url, $this->image($this->proxyImage($cover), $title))}</p>";
        }
        $html .= "<p>{$this->link($url, $display)}</p>";

        if (($d['podcast_description'] ?? '') !== '') {
            $html .= '<p><small>' . $this->e(mb_substr($d['podcast_description'], 0, self::MAX_PREVIEW_LENGTH)) . '</small></p>';
        }

        return $html;
    }

    private function getAttachmentTitle(array $attachment): string
    {
        $type = $attachment['type'] ?? '';
        $d = $attachment[$type] ?? [];

        $titles = [
            'video' => (mb_stripos($d['title'] ?? '', 'Клип ') === 0)
                ? 'Clip: ' . $this->cleanClipTitle($d['title'])
                : 'Video: ' . ($d['title'] ?? ''),
            'clip' => 'Clip: ' . $this->cleanClipTitle($d['title'] ?? ''),
            'audio' => 'Music: ' . ($d['artist'] ?? '') . ' - ' . ($d['title'] ?? ''),
            'link' => (strpos($d['url'] ?? '', 'audio_playlist') !== false)
                ? 'Playlist: ' . ($d['title'] ?? '')
                : 'Link: ' . ($d['title'] ?? ''),
            'doc' => 'Document: ' . ($d['title'] ?? ''),
            'album' => 'Album: ' . ($d['title'] ?? ''),
            'poll' => 'Poll: ' . ($d['question'] ?? ''),
            'photo' => 'Photo',
            'article' => 'Article: ' . ($d['title'] ?? ''),
            'wall' => 'Attached post',
            'market' => 'Product: ' . ($d['title'] ?? ''),
            'audio_playlist' => 'Playlist: ' . ($d['title'] ?? ''),
            'video_playlist' => 'Video playlist: ' . ($d['title'] ?? ''),
            'podcast' => 'Podcast: ' . ($d['podcast_title'] ?? $d['title'] ?? ''),
            'event' => 'Event: ' . ($d['text'] ?? ''),
            'graffiti' => 'Graffiti',
            'group' => 'Group: ' . ($d['name'] ?? ''),
            'donut_link' => 'VK Donut',
            'textlive' => 'Text: ' . ($d['title'] ?? ''),
            'textpost' => 'Text: ' . ($d['title'] ?? ''),
            'textpost_publish' => 'Text: ' . ($d['title'] ?? ''),
            'situational_theme' => $d['title'] ?? '',
            'sticker' => 'Sticker',
        ];

        return $titles[$type] ?? '';
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

        $cleanLine = preg_replace('/\[([^\]|]+)\|([^\]]+)\]/u', '$2', trim($lines[$meaningfulIndex]));

        if (empty(trim($cleanLine))) {
            return ['', $text];
        }

        [$titlePart, $urlRemainder] = $this->cutAtFirstUrl($cleanLine);

        if ($titlePart === '') {
            return ['', $text];
        }

        if (mb_strlen($titlePart) <= self::MAX_TITLE_LENGTH) {
            if ($urlRemainder !== '') {
                $lines[$meaningfulIndex] = $urlRemainder;
            } else {
                unset($lines[$meaningfulIndex]);
                $lines = array_values($lines);
            }
            return [$titlePart, implode("\n", $lines)];
        }

        $cut = mb_substr($titlePart, 0, self::MAX_TITLE_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');
        $cutPos = ($lastSpace !== false && $lastSpace > self::MIN_TITLE_SPACE_POS) ? $lastSpace : self::MAX_TITLE_LENGTH;
        $title = mb_substr($titlePart, 0, $cutPos) . '...';
        $remainder = '...' . trim(mb_substr($titlePart, $cutPos));

        if ($urlRemainder !== '') {
            $remainder .= ' ' . $urlRemainder;
        }

        $lines[$meaningfulIndex] = $remainder;
        return [$title, implode("\n", $lines)];
    }

    private function cutAtFirstUrl(string $text): array
    {
        if (preg_match('~\s+https?://~iu', $text, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1];
            return [rtrim(trim(substr($text, 0, $pos)), ':;,'), trim(substr($text, $pos))];
        }

        if (preg_match('~\s+(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}/~iu', $text, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1];
            return [rtrim(trim(substr($text, 0, $pos)), ':;,'), trim(substr($text, $pos))];
        }

        return [trim($text), ''];
    }

    private function normalizePlaylistUrl(string $url): string
    {
        if (preg_match('/act=audio_playlist(-?\d+)_(\d+)/', $url, $m)) {
            return "https://vk.ru/music/playlist/{$m[1]}_{$m[2]}";
        }
        return $url;
    }

    private function cleanClipTitle(string $title): string
    {
        if (mb_stripos($title, 'Клип ') === 0) {
            return trim(mb_substr($title, mb_strlen('Клип ')));
        }
        return $title;
    }

    private function isLinkOnly(string $line): bool
    {
        $line = trim($line);
        if ($line === '') {
            return false;
        }
        return (bool)preg_match('~^(https?://[^\s]+|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:/[^\s]*)?)$~i', $line);
    }

    private function applyPlaceholders(string $text, array &$placeholders, int &$counter, string $pattern, callable $resolver): string
    {
        $result = preg_replace_callback($pattern, function (array $m) use (&$placeholders, &$counter, $resolver): string {
            $html = $resolver($m);
            if ($html === null) {
                return $m[0];
            }
            $marker = "___VK_PH_{$counter}___";
            $placeholders[$marker] = $html;
            $counter++;
            return $marker;
        }, $text);

        return $result ?? $text;
    }

    private function resolveVkLink(string $target, string $linkText): string
    {
        if (preg_match('/^https?:\/\//i', $target)) {
            return $this->safeLink($target, $linkText);
        }
        if (preg_match('/^(id|club|public|wall|post|event|market)([\-0-9_]+)$/i', $target, $tm)) {
            return $this->safeLink('https://vk.ru/' . strtolower($tm[1]) . $tm[2], $linkText);
        }
        return $this->safeLink('https://vk.ru/' . $target, $linkText);
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
        if ($url === '' || preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return $this->e($text !== '' ? $text : $url);
        }
        return "<a href='{$this->e($url)}' target='_blank' rel='noopener noreferrer'>{$this->e($text !== '' ? $text : $url)}</a>";
    }

    private function link(string $url, string $text): string
    {
        return $this->linkHtml($url, $this->e($text));
    }

    private function linkHtml(string $url, string $html): string
    {
        return "<a href='{$this->e($url)}' target='_blank' rel='noopener noreferrer'>{$html}</a>";
    }

    private function image(string $url, string $alt): string
    {
        return "<img src='{$this->e($url)}' alt='{$this->e($alt)}'>";
    }

    private function proxyImage(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (function_exists('getProxiedUri')) {
            return getProxiedUri($url);
        }
        return $url;
    }

    private function getRateLimitCacheKey(): string
    {
        return 'vk2_rate_limit_' . md5($this->getOption('access_token') ?? '');
    }

    private function handleError(string $code, string $details = ''): void
    {
        $message = self::ERROR_MESSAGES[$code] ?? 'Unknown error';
        if ($details !== '') {
            $message .= ' ' . $details;
        }
        throwServerException($message);
    }
}