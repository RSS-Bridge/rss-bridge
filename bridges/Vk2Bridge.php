<?php

declare(strict_types=1);

class Vk2Bridge extends BridgeAbstract
{
    const NAME = 'VK';
    const URI = 'https://vk.com';
    const DESCRIPTION = 'Returns posts from the public feed. Needs personal API key';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 900;

    const PARAMETERS = [
        [
            'u' => [
                'name' => 'Name of group or profile',
                'type' => 'text',
                'required' => true,
                'title' => 'Name from URL. Example: rebel_jack from https://vk.com/rebel_jack'
            ],
            'hide_reposts' => [
                'name' => 'Hide reposts',
                'type' => 'checkbox',
                'title' => 'Check this box to hide reposts from feed items'
            ],
            'limit' => [
                'name' => 'Number of posts',
                'type' => 'number',
                'defaultValue' => 20,
            ]
        ]
    ];

    const CONFIGURATION = [
        'access_token' => [
            'required' => true,
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://vk.com/groupname' => ['u' => 'groupname'],
    ];

    const ATTACHMENT_RENDERERS = [
        'photo' => 'renderPhoto',
        'video' => 'renderVideo',
        'audio' => 'renderAudio',
        'doc' => 'renderDoc',
        'link' => 'renderLink',
        'note' => 'renderNote',
        'poll' => 'renderPoll',
        'album' => 'renderAlbum',
    ];

    const TITLE_FALLBACK_MAP = [
        'video' => 'getVideoTitle',
        'audio' => 'getAudioTitle',
        'link' => 'getLinkTitle',
        'doc' => 'getDocTitle',
        'album' => 'getAlbumTitle',
        'poll' => 'getPollTitle',
        'photo' => 'getPhotoTitle',
    ];

    const RATE_LIMIT_ERRORS = [
        6 => 15,
        29 => 1800,
    ];

    protected array $ownerNames = [];
    protected ?string $pageName = null;
    private string $urlRegex = '/vk\.com\/([\w.]+)/';

    public function getURI(): string
    {
        $u = $this->getInput('u');
        if (!is_null($u)) {
            return urljoin(static::URI, urlencode($u));
        }
        return parent::getURI();
    }

    public function getName(): string
    {
        if ($this->pageName) {
            return $this->pageName;
        }
        return parent::getName();
    }

    public function detectParameters($url): ?array
    {
        if (preg_match($this->urlRegex, $url, $matches)) {
            return ['u' => $matches[1]];
        }
        return null;
    }

    protected function getPostURI(array $post): string
    {
        $ownerId = $post['owner_id'] ?? 0;
        $id = $post['id'] ?? 0;
        $r = 'https://vk.com/wall' . $ownerId . '_' . $id;

        if (isset($post['reply_post_id'])) {
            $replyId = $post['reply_post_id'];
            $threadId = $post['parents_stack'][0] ?? $replyId;
            $r .= '?reply=' . $id . '&thread=' . $threadId;
        }

        return $r;
    }

    private function e($value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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

    protected function generateContentFromPost(array $post, bool $extractTitle = true): string
    {
        $text = $post['text'] ?? '';

        if ($extractTitle) {
            [, $text] = $this->splitFirstLine($text);
        }

        $text = trim($text);

        $ret = '';
        if (!empty($text)) {
            $ret = $this->e($text);
            $ret = nl2br($ret);

            $ret = preg_replace_callback(
                '~(https?://[^\s<|]+)|((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?:/[^\s<|]*)?)~i',
                function ($matches) {
                    if (!empty($matches[1])) {
                        return '<a href="' . $matches[1] . '" target="_blank" rel="noopener noreferrer">' . $matches[1] . '</a>';
                    }
                    if (!empty($matches[2])) {
                        return '<a href="https://' . $matches[2] . '" target="_blank" rel="noopener noreferrer">' . $matches[2] . '</a>';
                    }
                    return $matches[0];
                },
                $ret
            );

            $ret = preg_replace(
                '/\[(id|club|public)?([a-zA-Z0-9_.]+)\|([^\]]+)\]/i',
                '<a href="https://vk.com/$1$2" target="_blank" rel="noopener noreferrer">$3</a>',
                $ret
            );

            $ret = "<p>$ret</p>";
        }

        foreach ($post['attachments'] ?? [] as $attachment) {
            $ret .= $this->renderAttachment($attachment);
        }

        return $ret;
    }

    protected function renderAttachment(array $attachment): string
    {
        $type = $attachment['type'] ?? '';

        if (isset(self::ATTACHMENT_RENDERERS[$type])) {
            return call_user_func([$this, self::ATTACHMENT_RENDERERS[$type]], $attachment);
        }

        return "<p>Unknown attachment type: {$type}</p>";
    }

    private function renderPhoto(array $attachment): string
    {
        $photoUrl = $this->proxyImage($this->getImageURLWithLargestWidth($attachment['photo']['sizes'] ?? []));
        $text = $attachment['photo']['text'] ?? '';
        return "<p>{$this->image($photoUrl, $text)}</p>";
    }

    private function renderVideo(array $attachment): string
    {
        $title = $attachment['video']['title'] ?? 'Video';
        $ownerId = $attachment['video']['owner_id'] ?? 0;
        $id = $attachment['video']['id'] ?? 0;
        $videoType = $attachment['video']['type'] ?? 'video';
        $duration = $attachment['video']['duration'] ?? 0;

        $imageData = $attachment['video']['image'] ?? [];
        $photoUrl = $this->proxyImage($this->getImageURLWithLargestWidth($imageData));

        if ($videoType === 'story') {
            $href = "https://vk.com/clip{$ownerId}_{$id}";
            $title = "Clip: {$title} ({$duration} sec)";
        } else {
            $href = "https://vk.com/video{$ownerId}_{$id}";
            $title = "Video: {$title}";
        }

        $eTitle = $this->e($title);
        return "<p>{$this->linkHtml($href, $this->image($photoUrl, $title) . "<br/>{$eTitle}")}</p>";
    }

    private function renderAudio(array $attachment): string
    {
        $artist = $attachment['audio']['artist'] ?? '';
        $title = $attachment['audio']['title'] ?? '';
        return "<p>Audio: {$this->e($artist)} - {$this->e($title)}</p>";
    }

    private function renderDoc(array $attachment): string
    {
        $docUrl = $attachment['doc']['url'] ?? '#';
        $title = $attachment['doc']['title'] ?? 'Document';
        $ext = $attachment['doc']['ext'] ?? '';

        if ($ext === 'gif') {
            $gifUrl = $this->proxyImage($docUrl);
            return "<p>{$this->image($gifUrl, $title)}</p>";
        }

        return "<p>{$this->link($docUrl, "Document: {$title}")}</p>";
    }

    private function renderLink(array $attachment): string
    {
        $url = str_replace('https://m.vk.com', 'https://vk.com', $attachment['link']['url'] ?? '#');
        $title = $attachment['link']['title'] ?? $url;

        if (isset($attachment['link']['photo']['sizes'])) {
            $photoUrl = $this->proxyImage($this->getImageURLWithLargestWidth($attachment['link']['photo']['sizes']));
            $eTitle = $this->e($title);
            return "<p>{$this->linkHtml($url, $this->image($photoUrl, $title) . "<br>{$eTitle}")}</p>";
        }

        return "<p>{$this->link($url, $title)}</p>";
    }

    private function renderNote(array $attachment): string
    {
        $title = $attachment['note']['title'] ?? 'Note';
        $url = $attachment['note']['view_url'] ?? '#';
        return "<p>{$this->link($url, $title)}</p>";
    }

    private function renderPoll(array $attachment): string
    {
        $question = $attachment['poll']['question'] ?? 'Poll';
        $voteCount = $attachment['poll']['votes'] ?? 0;
        $answers = $attachment['poll']['answers'] ?? [];

        $html = "<p>Poll: {$this->e($question)} ({$voteCount} votes)<br />";
        foreach ($answers as $answer) {
            $text = $answer['text'] ?? '';
            $votes = $answer['votes'] ?? 0;
            $rate = $answer['rate'] ?? 0;
            $html .= "* {$this->e($text)}: {$votes} ({$rate}%)<br />";
        }
        return $html . '</p>';
    }

    private function renderAlbum(array $attachment): string
    {
        $album = $attachment['album'] ?? [];
        $ownerId = $album['owner_id'] ?? 0;
        $id = $album['id'] ?? 0;
        $url = "https://vk.com/album{$ownerId}_{$id}";
        $title = 'Album: ' . ($album['title'] ?? '');
        $photoUrl = $this->proxyImage($this->getImageURLWithLargestWidth($album['thumb']['sizes'] ?? []));
        $eTitle = $this->e($title);
        return "<p>{$this->linkHtml($url, $this->image($photoUrl, $title) . "<br>{$eTitle}")}</p>";
    }

    protected function getImageURLWithLargestWidth(array $items): string
    {
        if (empty($items)) {
            return '';
        }
        usort($items, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
        return $items[0]['url'] ?? '';
    }

    public function collectData(): void
    {
        if ($this->cache->get($this->getRateLimitCacheKey())) {
            throwRateLimitException();
        }

        $u = $this->getInput('u');
        $ownerId = $this->detectOwnerId($u);

        usleep(350000);

        $limit = (int)($this->getInput('limit') ?? 20);
        $limit = max(1, min(100, $limit));

        $r = $this->api('wall.get', [
            'owner_id' => $ownerId,
            'extended' => '1',
            'count' => $limit,
        ]);

        if (!isset($r['response'])) {
            $this->handleError('invalid_api_response', 'wall.get');
        }

        $response = $r['response'];

        foreach ($response['profiles'] ?? [] as $profile) {
            $this->ownerNames[$profile['id']] = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        }

        foreach ($response['groups'] ?? [] as $group) {
            $this->ownerNames[-$group['id']] = $group['name'] ?? 'Unknown';
        }

        $this->generateFeed($r);
    }

    private function detectOwnerId(string $u): int
    {
        if (preg_match('/^(club|public)(\d+)$/', $u, $matches)) {
            return -intval($matches[2]);
        }

        if (preg_match('/^id(\d+)$/', $u, $matches)) {
            return intval($matches[1]);
        }

        $r = $this->api('groups.getById', ['group_ids' => $u], [100]);

        if (isset($r['response']['groups'][0]['id'])) {
            return -$r['response']['groups'][0]['id'];
        }

        if (isset($r['response'][0]['id'])) {
            return -$r['response'][0]['id'];
        }

        usleep(350000);
        $r = $this->api('users.get', ['user_ids' => $u]);

        if (isset($r['response'][0]['id'])) {
            return $r['response'][0]['id'];
        }

        $this->handleError('owner_not_found', "Short name '{$u}'");
    }

    protected function generateFeed(array $r): void
    {
        $ownerId = 0;
        $items = $r['response']['items'] ?? [];

        foreach ($items as $post) {
            if (($post['marked_as_ads'] ?? 0) === 1 || ($post['is_pinned'] ?? 0) === 1) {
                continue;
            }

            if (!$ownerId) {
                $ownerId = $post['owner_id'] ?? 0;
            }

            $isRepost = isset($post['copy_history'][0]);
            $displayPost = $isRepost ? $post['copy_history'][0] : $post;

            if ($isRepost && $this->getInput('hide_reposts')) {
                continue;
            }

            $content = $this->generateContentFromPost($displayPost, true);

            $fromId = $displayPost['from_id'] ?? $displayPost['owner_id'] ?? 0;

            $author = '';
            if ($fromId !== $ownerId) {
                $author = $this->ownerNames[$fromId] ?? 'Unknown';
            }

            $this->items[] = [
                'content'   => $content,
                'timestamp' => $displayPost['date'] ?? time(),
                'author'    => $author,
                'title'     => $this->getTitle($displayPost),
                'uri'       => $this->getPostURI($displayPost),
            ];
        }

        if ($ownerId && isset($this->ownerNames[$ownerId])) {
            $this->pageName = $this->ownerNames[$ownerId];
        }
    }

    protected function getTitle(array $post): string
    {
        [$title, ] = $this->splitFirstLine($post['text'] ?? '');

        if (!empty($title)) {
            return $title;
        }

        return $this->getFallbackTitle($post);
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

        if (mb_strlen($cleanLine) <= 50) {
            $bodyLines = $lines;
            unset($bodyLines[$meaningfulIndex]);
            $bodyLines = array_values($bodyLines);
            return [$cleanLine, implode("\n", $bodyLines)];
        }

        $cut = mb_substr($cleanLine, 0, 50);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 25) {
            $cutPos = $lastSpace;
        } else {
            $cutPos = 50;
        }

        $title = mb_substr($cleanLine, 0, $cutPos) . '...';
        $remainder = mb_substr($originalLine, $cutPos);

        $bodyLines = $lines;
        $bodyLines[$meaningfulIndex] = '...' . $remainder;

        return [$title, implode("\n", $bodyLines)];
    }

    private function isLinkOnly(string $line): bool
    {
        $line = trim($line);
        if (empty($line)) {
            return false;
        }
        return (bool)preg_match('~^(https?://[^\s]+|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(?:/[^\s]*)?)$~i', $line);
    }

    private function getFallbackTitle(array $post): string
    {
        foreach ($post['attachments'] ?? [] as $attachment) {
            $type = $attachment['type'] ?? '';
            if (isset(self::TITLE_FALLBACK_MAP[$type])) {
                return call_user_func([$this, self::TITLE_FALLBACK_MAP[$type]], $attachment);
            }
        }

        return 'untitled';
    }

    private function getVideoTitle(array $attachment): string
    {
        $videoType = $attachment['video']['type'] ?? 'video';
        $title = $attachment['video']['title'] ?? '';
        return $videoType === 'story' ? 'Clip: ' . $title : 'Video: ' . $title;
    }

    private function getAudioTitle(array $attachment): string
    {
        $artist = $attachment['audio']['artist'] ?? '';
        $title = $attachment['audio']['title'] ?? '';
        return "Audio: {$artist} - {$title}";
    }

    private function getLinkTitle(array $attachment): string
    {
        return 'Link: ' . ($attachment['link']['title'] ?? '');
    }

    private function getDocTitle(array $attachment): string
    {
        return 'Document: ' . ($attachment['doc']['title'] ?? '');
    }

    private function getAlbumTitle(array $attachment): string
    {
        return 'Album: ' . ($attachment['album']['title'] ?? '');
    }

    private function getPollTitle(array $attachment): string
    {
        return 'Poll: ' . ($attachment['poll']['question'] ?? '');
    }

    private function getPhotoTitle(array $attachment): string
    {
        return 'Photo';
    }

    private function proxyImage(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (function_exists('proxyImageUrl')) {
            return proxyImageUrl($url);
        }

        return $url;
    }

    private function getRateLimitCacheKey(): string
    {
        $token = $this->getOption('access_token') ?? '';
        return 'vk2_rate_limit_' . md5($token);
    }

    protected function api(string $method, array $params, array $expected_error_codes = []): array
    {
        $access_token = $this->getOption('access_token');
        if (!$access_token) {
            $this->handleError('missing_access_token');
        }

        $params['v'] = '5.199';
        $url = 'https://api.vk.com/method/' . $method . '?' . http_build_query($params);

        $response = getContents($url, ['Authorization: Bearer ' . $access_token]);
        $r = json_decode($response, true);

        if (!is_array($r)) {
            $this->handleError('invalid_json');
        }

        if (!isset($r['error'])) {
            return $r;
        }

        $errorCode = $r['error']['error_code'] ?? 0;
        if (in_array($errorCode, $expected_error_codes, true)) {
            return $r;
        }

        if (isset(self::RATE_LIMIT_ERRORS[$errorCode])) {
            $this->cache->set($this->getRateLimitCacheKey(), true, self::RATE_LIMIT_ERRORS[$errorCode]);
        }

        $errorMsg = $r['error']['error_msg'] ?? 'Unknown error';
        $this->handleError('api_error', "{$errorMsg} ({$errorCode})");
    }

    private function handleError(string $code, string $details = ''): void
    {
        $messages = [
            'owner_not_found' => 'Could not detect owner id. Please check if the short name is correct and the page is not blocked or deleted',
            'invalid_api_response' => 'Invalid API response from',
            'missing_access_token' => 'You cannot run VK API methods without access_token',
            'invalid_json' => 'Invalid JSON response from VK API',
            'api_error' => 'API returned error:',
        ];

        $message = $messages[$code] ?? 'Unknown error';
        if ($details) {
            $message .= ' ' . $details;
        }

        throwServerException($message);
    }
}