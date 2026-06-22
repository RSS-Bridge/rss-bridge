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
    const FILE_DOMAIN = 'https://file.pawchive.st';
    const THUMBNAIL_DOMAIN = 'https://img.pawchive.st';

    const PARAMETERS = [[
        'service' => [
            'name' => 'Content service',
            'type' => 'list',
            'required' => true,
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
            'name' => 'Hide videos completely',
            'type' => 'checkbox',
            'title' => 'Completely hide video files from posts to save bandwidth. Videos will not be shown or linked at all.',
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
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        'txt' => 'text/plain',
    ];

    private const CSS = [
        'image' => 'max-width:100%;height:auto;display:block;margin:10px 0',
        'video' => 'max-width:100%;height:auto;display:block;margin:10px 0',
        'file-link' => 'display:inline-block;margin:10px 0;color:#0066cc;text-decoration:none',
        'url-link' => 'color:#0066cc;text-decoration:none;word-break:break-all',
        'video-indicator' => 'display:inline-block;margin:10px 0;padding:8px 12px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;color:#666;font-style:italic',
    ];

    const CONFIGURATION = [
        'session' => [
            'required' => true,
        ],
    ];

    private const HTTP_HEADERS = [
        'Accept: application/json, text/css, */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];

    private ?string $author = null;
    private array $mimeCache = [];

    private function baseURI(): string
    {
        return 'https://pawchive.st/';
    }

    private function getFileUrl(string $path, ?string $filename = null): string
    {
        $url = self::FILE_DOMAIN . '/data' . $path;
        return $filename !== null ? $url . '?f=' . urlencode($filename) : $url;
    }

    private function getThumbnailUrl(string $path, ?string $filename = null): string
    {
        $url = self::THUMBNAIL_DOMAIN . '/thumbnail/data' . $path;
        return $filename !== null ? $url . '?f=' . urlencode($filename) : $url;
    }

    private function getMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return $this->mimeCache[$ext] ??= self::MIME_TYPES[$ext] ?? 'application/octet-stream';
    }

    private function isImage(string $filename): bool
    {
        return strpos($this->getMimeType($filename), 'image/') === 0;
    }

    private function isVideo(string $filename): bool
    {
        return strpos($this->getMimeType($filename), 'video/') === 0;
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

    private function renderFileLink(string $url, string $filename): string
    {
        return sprintf(
            '<a href="%s" style="%s">%s</a>',
            htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            self::CSS['file-link'],
            htmlspecialchars($filename, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }

    private function renderHiddenVideoIndicator(int $count): string
    {
        $text = $count === 1 ? '+1 video in post' : sprintf('+%d videos in post', $count);
        return sprintf('<div style="%s">%s</div>', self::CSS['video-indicator'], htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function renderExternalLink(string $url): string
    {
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return sprintf(
            '<div style="margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:5px;"><strong>External Link:</strong><br><a href="%s" style="%s">%s</a></div>',
            $escapedUrl,
            self::CSS['url-link'],
            $escapedUrl
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $endpoint): array
    {
        $service = $this->getInput('service');
        $url = $this->baseURI() . self::API_PREFIX . $service . $endpoint;
        
        $headers = array_merge(self::HTTP_HEADERS, [
            'Cookie: session=' . $this->getOption('session')
        ]);

        try {
            $api_response = getContents($url, $headers);
            $data = Json::decode($api_response);
            
            return is_array($data) ? $data : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function collectFiles(array $post): array
    {
        $files = [];
        
        if (!empty($post['file']['path'])) {
            $files[] = $post['file'];
        }
        
        if (!empty($post['attachments']) && is_array($post['attachments'])) {
            foreach ($post['attachments'] as $file) {
                if (!empty($file['path'])) {
                    $files[] = $file;
                }
            }
        }
        
        return $files;
    }

    private function processFiles(array $files, bool $hasFull, bool $hideVideos, string &$contentHtml, array &$enclosures): int
    {
        $hiddenVideoCount = 0;

        foreach ($files as $file) {
            $fileName = $file['name'] ?? basename($file['path']);
            
            if ($this->isVideo($fileName) && $hideVideos) {
                $hiddenVideoCount++;
                continue;
            }
            
            $fileUrl = $hasFull ? $this->getFileUrl($file['path'], $fileName) : $this->getThumbnailUrl($file['path'], $fileName);
            
            if ($this->isImage($fileName)) {
                $contentHtml .= $this->renderImage($fileUrl, $fileName);
            } elseif ($this->isVideo($fileName)) {
                $contentHtml .= $this->renderVideo($fileUrl, $this->getMimeType($fileName));
            } else {
                if ($hasFull) {
                    $enclosures[] = $fileUrl;
                }
                $contentHtml .= $this->renderFileLink($fileUrl, $fileName);
            }
        }

        return $hiddenVideoCount;
    }

    public function collectData(): void
    {
        $user = '/user/' . $this->getInput('user');
        
        $profile = $this->getJson("{$user}/profile");
        $this->author = $profile['name'] ?? 'Unknown';

        $q = urlencode($this->getInput('q') ?? '');
        $json = $this->getJson("{$user}?q={$q}");
        
        if (empty($json)) {
            return;
        }

        $hideVideos = (bool) $this->getInput('hide_videos');
        $elements = array_slice($json, 0, $this->getInput('limit'));

        foreach ($elements as $post) {
            $item = $this->createItem($post, $hideVideos);
            $this->items[] = $item;
        }
    }

    private function createItem(array $post, bool $hideVideos): array
    {
        $content = $this->sanitizeHtml($post['content'] ?? '');
        $content = $this->formatUrlsInText($content);
        
        $item = [
            'author' => $this->author,
            'content' => $content,
            'timestamp' => strtotime($post['published'] ?? $post['added']),
            'title' => $this->sanitizeText($post['title'] ?? 'Post ' . $post['id']),
            'uid' => $post['id'],
            'uri' => $this->getURI() . '/post/' . $post['id'],
            'enclosures' => [],
        ];

        $contentHtml = $item['content'];
        
        if (!empty($post['embed']['url'])) {
            $contentHtml .= $this->renderExternalLink($post['embed']['url']);
            $item['enclosures'][] = $post['embed']['url'];
        }
        
        $files = $this->collectFiles($post);
        $hasFull = $post['has_full'] ?? true;
        
        $hiddenVideoCount = $this->processFiles($files, $hasFull, $hideVideos, $contentHtml, $item['enclosures']);
        
        if ($hiddenVideoCount > 0) {
            $contentHtml .= $this->renderHiddenVideoIndicator($hiddenVideoCount);
        }
        
        $item['content'] = $contentHtml;
        
        return $item;
    }

    public function getName(): string
    {
        return $this->author ?? parent::getName();
    }

    public function getURI(): string
    {
        $service = $this->getInput('service');
        $user = $this->getInput('user');

        return $this->baseURI() . $service . '/user/' . $user;
    }
}