<?php

declare(strict_types=1);

class PawchiveBridge extends BridgeAbstract
{
    const NAME = 'Pawchive';
    const URI = 'https://pawchive.st/';
    const DESCRIPTION = 'Returns posts from Pawchive. Kemono is dead, long live the Pawchive!';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    const API_PREFIX = 'api/v1/';

    const PARAMETERS = [[
        'service' => [
            'name' => 'Content service',
            'type' => 'list',
            'defaultValue' => 'patreon',
            'values' => self::ACTIVE_SERVICES,
            'title' => 'Pawchive now support only Patreon and Pixiv Fanbox'
        ],
        'user' => [
            'name' => 'Creator ID',
            'type' => 'number',
            'required' => true
        ],
        'q' => [
            'name' => 'Search query',
            'type' => 'text',
            'required' => false
        ],
        'limit' => self::LIMIT,
        'hide_videos' => [
            'name' => 'Hide videos & attachments',
            'type' => 'checkbox',
            'title' => 'Show only image previews. Videos, full-size images and file attachments will be completely hidden',
            'defaultValue' => false
        ],
        'hide_empty' => [
            'name' => 'Hide posts without media',
            'type' => 'checkbox',
            'title' => 'Skip posts without media (text-only posts will be hidden)',
            'defaultValue' => false
        ],
    ]];

    private const ALL_SERVICES = [
        'Pixiv Fanbox' => 'fanbox',
        'Patreon' => 'patreon',
        'Fantia' => 'fantia',
        'Boosty' => 'boosty',
        'Gumroad' => 'gumroad',
        'SubscribeStar' => 'subscribestar',
        'OnlyFans' => 'onlyfans',
        'Discord' => 'discord',
        'Fansly' => 'fansly',
    ];

    private const ACTIVE_SERVICES = [
        'Pixiv Fanbox' => 'fanbox',
        'Patreon' => 'patreon',
    ];

    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'jfif' => 'image/jpeg',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'ico' => 'image/x-icon',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'm4v' => 'video/x-m4v',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'm4a' => 'audio/mp4',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'txt' => 'text/plain',
        'psd' => 'application/x-photoshop',
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif', 'bmp', 'svg', 'tiff', 'tif', 'ico'];
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'm4v'];

    private const CSS = [
        'image' => 'max-width:100%;height:auto;display:block;margin:0',
        'video' => 'max-width:100%;height:auto;display:block;margin:0',
        'file-link' => 'display:inline-block;margin:0;color:#0066cc;text-decoration:none',
        'url-link' => 'color:#0066cc;text-decoration:none;word-break:break-all',
        'file-container' => 'margin:10px 0',
        'external-link-container' => 'margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:5px',
        'attachments-container' => 'margin-top:20px;padding:10px;background:#f9f9f9;border:1px solid #ddd;border-radius:5px',
        'attachments-heading' => 'margin:0 0 8px 0;font-weight:bold;color:#333',
        'attachments-list' => 'margin:0;padding:0;list-style:none',
        'attachments-item' => 'margin:4px 0',
    ];

    const CONFIGURATION = [
        'session' => [
            'required' => true,
        ],
    ];

    private const HTTP_HEADERS = [
        'Accept: application/json, text/css, */*',
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
    ];

    private const DOMAINS = ['pawchive.st', 'pawchive.pw'];
    private const CACHE_KEY_ACTIVE_DOMAIN = 'active_domain';

    private ?string $author = null;
    private ?string $authorAvatarUrl = null;
    private array $mimeCache = [];
    private ?string $activeDomainHost = null;

    private function getActiveDomainHost(): string
    {
        if ($this->activeDomainHost === null) {
            $cached = $this->loadCacheValue(self::CACHE_KEY_ACTIVE_DOMAIN);
            if (is_string($cached) && in_array($cached, self::DOMAINS, true)) {
                $this->activeDomainHost = $cached;
            } else {
                $this->activeDomainHost = self::DOMAINS[0];
            }
        }
        return $this->activeDomainHost;
    }

    private function setActiveDomainHost(string $host): void
    {
        $this->activeDomainHost = $host;
        $this->saveCacheValue(self::CACHE_KEY_ACTIVE_DOMAIN, $host, self::CACHE_TIMEOUT);
    }

    private function baseURI(): string
    {
        return 'https://' . $this->getActiveDomainHost() . '/';
    }

    private function getFileDomain(): string
    {
        return 'https://file.' . $this->getActiveDomainHost();
    }

    private function getThumbnailDomain(): string
    {
        return 'https://img.' . $this->getActiveDomainHost();
    }

    private function getFileUrl(string $path, ?string $filename = null): string
    {
        $url = $this->getFileDomain() . '/data' . $path;
        return $filename !== null ? $url . '?f=' . urlencode($filename) : $url;
    }

    private function getThumbnailUrl(string $path, ?string $filename = null): string
    {
        $url = $this->getThumbnailDomain() . '/thumbnail/data' . $path;
        return $filename !== null ? $url . '?f=' . urlencode($filename) : $url;
    }

    private function getAvatarUrl(string $service, string $userId): string
    {
        return $this->baseURI() . 'icons/' . $service . '/' . $userId;
    }

    private function normalizeUrls(string $content): string
    {
        $activeHost = $this->getActiveDomainHost();
        foreach (self::DOMAINS as $host) {
            if ($host !== $activeHost) {
                $content = str_replace('https://' . $host, 'https://' . $activeHost, $content);
                $content = str_replace('https://file.' . $host, 'https://file.' . $activeHost, $content);
                $content = str_replace('https://img.' . $host, 'https://img.' . $activeHost, $content);
            }
        }
        return $content;
    }

    private function getMimeType(string $filename): string
    {
        $cleanFilename = preg_replace('/[^\x20-\x7E]/', '', $filename);
        $cleanFilename = trim($cleanFilename);
        $ext = strtolower(trim(pathinfo($cleanFilename, PATHINFO_EXTENSION)));
        return $this->mimeCache[$ext] ??= self::MIME_TYPES[$ext] ?? 'application/octet-stream';
    }

    private function getExtension(string $filename): string
    {
        $cleanFilename = preg_replace('/[^\x20-\x7E]/', '', $filename);
        $cleanFilename = trim($cleanFilename);
        return strtolower(trim(pathinfo($cleanFilename, PATHINFO_EXTENSION)));
    }

    private function isImageByExtension(string $filename): bool
    {
        return in_array($this->getExtension($filename), self::IMAGE_EXTENSIONS, true);
    }

    private function isVideoByExtension(string $filename): bool
    {
        return in_array($this->getExtension($filename), self::VIDEO_EXTENSIONS, true);
    }

    private function isMediaByExtension(string $filename): bool
    {
        return $this->isImageByExtension($filename) || $this->isVideoByExtension($filename);
    }

    private function hasMedia(array $post): bool
    {
        if (!empty($post['file']['path'])) {
            $name = $post['file']['name'] ?? basename($post['file']['path']);
            if ($this->isMediaByExtension((string)$name)) {
                return true;
            }
        }
        if (!empty($post['attachments']) && is_array($post['attachments'])) {
            foreach ($post['attachments'] as $file) {
                if (!empty($file['path'])) {
                    $name = $file['name'] ?? basename($file['path']);
                    if ($this->isMediaByExtension((string)$name)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function cleanUnicodeCharacters(string $text): string
    {
        $text = preg_replace_callback(
            '/[\x{10000}-\x{10FFFF}]/u',
            fn(): string => '',
            $text
        );
        return preg_replace(['/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '/[\x{FFFE}\x{FEFF}]/u'], '', $text);
    }

    private function formatUrlsInText(string $text): string
    {
        $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $inAnchor = false;
        $result = '';
        $style = self::CSS['url-link'];
        foreach ($parts as $part) {
            if (preg_match('/^<a\b/i', $part)) {
                $inAnchor = true;
            } elseif (preg_match('/^<\/a>$/i', $part)) {
                $inAnchor = false;
            } elseif (!$inAnchor && trim($part) !== '') {
                $part = preg_replace_callback(
                    '/(https?:\/\/[^\s<>\"]+)/i',
                    fn(array $matches): string => sprintf(
                        '<a href="%s" style="%s">%s</a>',
                        htmlspecialchars($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                        $style,
                        htmlspecialchars($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
                    ),
                    $part
                );
            }
            $result .= $part;
        }
        return $result;
    }

    private function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $html = $this->cleanUnicodeCharacters($html);
        $replacements = [
            '/<p>\s*<\/p>/i' => '',
            '/<div>\s*<\/div>/i' => '',
            '/<span>\s*<\/span>/i' => '',
            '/(<br\s*\/?>\s*){3,}/i' => '<br><br>',
            '/^\s*\n/m' => '',
            '/\n{3,}/' => "\n\n",
            '/&nbsp;/i' => ' ',
            '/\s{2,}/' => ' ',
        ];
        return trim(preg_replace(array_keys($replacements), array_values($replacements), $html));
    }

    private function sanitizeText(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $text = $this->cleanUnicodeCharacters($text);
        $replacements = [
            '/\h+/' => ' ',
            '/^\s*\n/m' => '',
            '/\n{3,}/' => "\n\n",
        ];
        return trim(preg_replace(array_keys($replacements), array_values($replacements), $text));
    }

    private function renderImage(string $url, string $alt): string
    {
        return sprintf(
            '<img src="%s" alt="%s" style="%s">',
            htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            self::CSS['image']
        );
    }

    private function renderVideo(string $url, string $mimeType): string
    {
        return sprintf(
            '<video controls style="%s"><source src="%s" type="%s">Your browser does not support the video tag.</video>',
            self::CSS['video'],
            htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $mimeType
        );
    }

    private function renderExternalLink(string $url): string
    {
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return sprintf(
            '<div style="%s"><strong>External Link:</strong><br><a href="%s" style="%s">%s</a></div>',
            self::CSS['external-link-container'],
            $escapedUrl,
            self::CSS['url-link'],
            $escapedUrl
        );
    }

    private function getJson(string $endpoint): array
    {
        $service = $this->getInput('service');
        $headers = array_merge(self::HTTP_HEADERS, [
            'Cookie: session=' . $this->getOption('session')
        ]);
        $curlOptions = [
            CURLOPT_FOLLOWLOCATION => false,
        ];
        $activeHost = $this->getActiveDomainHost();
        $hostsToTry = [$activeHost];
        foreach (self::DOMAINS as $host) {
            if ($host !== $activeHost) {
                $hostsToTry[] = $host;
            }
        }
        foreach ($hostsToTry as $host) {
            $url = 'https://' . $host . '/' . self::API_PREFIX . $service . $endpoint;
            try {
                $api_response = getContents($url, $headers, $curlOptions);
                $data = Json::decode($api_response);
                if (is_array($data)) {
                    $this->setActiveDomainHost($host);
                    return $data;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return [];
    }

    private function collectFiles(array $post): array
    {
        $files = [];
        if (!empty($post['file']['path'])) {
            $file = $post['file'];
            $file['name'] = isset($file['name']) ? trim(preg_replace('/[^\x20-\x7E]/', '', (string)$file['name'])) : null;
            $file['path'] = trim(preg_replace('/[^\x20-\x7E]/', '', (string)$file['path']));
            $files[] = $file;
        }
        if (!empty($post['attachments']) && is_array($post['attachments'])) {
            foreach ($post['attachments'] as $file) {
                if (!empty($file['path'])) {
                    $file['name'] = isset($file['name']) ? trim(preg_replace('/[^\x20-\x7E]/', '', (string)$file['name'])) : null;
                    $file['path'] = trim(preg_replace('/[^\x20-\x7E]/', '', (string)$file['path']));
                    $files[] = $file;
                }
            }
        }
        return $files;
    }

    private function processFiles(array $files, bool $hideAttachments, string &$contentHtml, array &$downloadLinks): void
    {
        $containerStyle = self::CSS['file-container'];
        foreach ($files as $file) {
            $fileName = $file['name'] ?? basename($file['path']);
            $fileName = trim($fileName);
            if ($fileName === '') {
                continue;
            }
            $thumbnailUrl = $this->getThumbnailUrl($file['path'], $fileName);
            $fullUrl = $this->getFileUrl($file['path'], $fileName);
            if ($this->isImageByExtension($fileName)) {
                $contentHtml .= sprintf('<div style="%s">%s</div>', $containerStyle, $this->renderImage($thumbnailUrl, $fileName));
                if (!$hideAttachments) {
                    $downloadLinks[] = ['url' => $fullUrl, 'name' => $fileName];
                }
            } elseif ($this->isVideoByExtension($fileName)) {
                if (!$hideAttachments) {
                    $contentHtml .= sprintf('<div style="%s">%s</div>', $containerStyle, $this->renderVideo($fullUrl, $this->getMimeType($fileName)));
                    $downloadLinks[] = ['url' => $fullUrl, 'name' => $fileName];
                }
            } else {
                if (!$hideAttachments) {
                    $downloadLinks[] = ['url' => $fullUrl, 'name' => $fileName];
                }
            }
        }
    }

    private function renderAttachmentsBlock(array $downloadLinks): string
    {
        if (empty($downloadLinks)) {
            return '';
        }
        $itemsHtml = '';
        $linkStyle = self::CSS['file-link'];
        $itemStyle = self::CSS['attachments-item'];
        foreach ($downloadLinks as $link) {
            $itemsHtml .= sprintf(
                '<li style="%s"><a href="%s" style="%s" download>%s</a></li>',
                $itemStyle,
                htmlspecialchars($link['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $linkStyle,
                htmlspecialchars($link['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
        }
        return sprintf(
            '<div style="%s"><h4 style="%s">Attachments</h4><ul style="%s">%s</ul></div>',
            self::CSS['attachments-container'],
            self::CSS['attachments-heading'],
            self::CSS['attachments-list'],
            $itemsHtml
        );
    }

    public function getIcon(): string
    {
        $icon = $this->authorAvatarUrl ?? parent::getIcon();
        return $this->normalizeUrls($icon);
    }

    public function getURI(): string
    {
        $service = $this->getInput('service');
        $user = $this->getInput('user');
        $uri = $this->baseURI() . $service . '/user/' . $user;
        return $this->normalizeUrls($uri);
    }

    public function getName(): string
    {
        return $this->author ?? parent::getName();
    }

    public function collectData(): void
    {
        $service = $this->getInput('service');
        $user = '/user/' . $this->getInput('user');
        $profile = $this->getJson("{$user}/profile");
        $this->author = $profile['name'] ?? 'Unknown';
        $this->authorAvatarUrl = $this->getAvatarUrl($service, (string)$this->getInput('user'));

        $queryParams = [];
        $q = $this->getInput('q');
        if ($q !== null && $q !== '') {
            $queryParams['q'] = $q;
        }
        $queryString = $queryParams ? '?' . http_build_query($queryParams) : '';

        $json = $this->getJson("{$user}{$queryString}");
        if (empty($json)) {
            return;
        }

        $hideAttachments = (bool)$this->getInput('hide_videos');
        $hideEmpty = (bool)$this->getInput('hide_empty');
        $limit = $this->getInput('limit');

        $count = 0;
        foreach ($json as $post) {
            if ($hideEmpty && !$this->hasMedia($post)) {
                continue;
            }
            $this->items[] = $this->createItem($post, $hideAttachments);
            $count++;
            if ($limit !== null && $count >= $limit) {
                break;
            }
        }
    }

    private function createItem(array $post, bool $hideAttachments): array
    {
        $content = $post['content'] ?? '';
        $content = $this->normalizeUrls($content);
        $content = $this->sanitizeHtml($content);
        $files = $this->collectFiles($post);
        foreach ($files as $file) {
            $fileName = $file['name'] ?? basename($file['path']);
            $fileName = trim($fileName);
            if ($fileName !== '') {
                $content = preg_replace('/(?<![a-zA-Z0-9])' . preg_quote($fileName, '/') . '(?![a-zA-Z0-9])/i', '', $content);
            }
        }
        $content = $this->formatUrlsInText($content);
        $item = [
            'author' => $this->author,
            'content' => $content,
            'timestamp' => strtotime($post['published'] ?? $post['added']),
            'title' => $this->sanitizeText($post['title'] ?? 'Post ' . $post['id']),
            'uid' => $post['id'],
            'uri' => $this->getURI() . '/post/' . $post['id'],
        ];
        $contentHtml = $item['content'];
        if (!empty($post['embed']['url'])) {
            $contentHtml .= $this->renderExternalLink($this->normalizeUrls($post['embed']['url']));
        }
        $downloadLinks = [];
        $this->processFiles($files, $hideAttachments, $contentHtml, $downloadLinks);
        $contentHtml .= $this->renderAttachmentsBlock($downloadLinks);
        $item['content'] = $contentHtml;
        return $item;
    }

    public function getItems(): array
    {
        $items = parent::getItems();
        foreach ($items as &$item) {
            if (isset($item['content'])) {
                $item['content'] = $this->normalizeUrls($item['content']);
            }
            if (isset($item['uri'])) {
                $item['uri'] = $this->normalizeUrls($item['uri']);
            }
        }
        return $items;
    }
}