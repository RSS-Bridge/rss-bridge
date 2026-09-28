<?php

class VkVideoBridge extends BridgeAbstract
{
    const NAME = 'VK Video';
    const URI = 'https://vkvideo.ru';
    const DESCRIPTION = 'Returns videos from a VK Video channel or playlist';
    const MAINTAINER = 'anlar';

    const PARAMETERS = [
        'Channel' => [
            'u' => [
                'name' => 'Channel',
                'exampleValue' => '@thoisoi',
                'required' => true,
            ],
        ],
        'Playlist' => [
            'p' => [
                'name' => 'Playlist',
                'exampleValue' => '-142758151_-4',
                'required' => true,
            ],
        ],
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://vkvideo.ru/@thoisoi' => ['u' => '@thoisoi'],
        'https://vkvideo.ru/playlist/-142758151_-4' => ['p' => '-142758151_-4'],
    ];

    // vkvideo.ru serves fully rendered pages (with videos embedded as JSON-LD)
    // to search engine crawlers, so a Googlebot user agent is used to fetch them.
    const USER_AGENT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private $feedName;

    public function getURI()
    {
        if ($this->getInput('u')) {
            return self::URI . '/' . $this->extractChannelHandle($this->getInput('u'));
        }
        if ($this->getInput('p')) {
            return self::URI . '/playlist/' . $this->extractPlaylistId($this->getInput('p'));
        }
        return parent::getURI();
    }

    public function getName()
    {
        return $this->feedName ?: parent::getName();
    }

    public function detectParameters($url)
    {
        if (str_contains($url, '/playlist/')) {
            return ['p' => $this->extractPlaylistId($url)];
        }
        if (preg_match('#vkvideo\.ru/@#', $url)) {
            return ['u' => $this->extractChannelHandle($url)];
        }
        return parent::detectParameters($url);
    }

    public function collectData()
    {
        $html = $this->fetchHtml($this->getURI());

        $gallery = $this->extractVideoGallery($html);
        if (!$gallery) {
            throwServerException('Could not find video data on the page');
        }

        if ($this->getInput('u')) {
            $this->feedName = $gallery['name'] ?? null;
            $this->items = $this->collectChannelItems($gallery);
        } else {
            $this->feedName = $this->extractPageTitle($html);
            $this->items = $this->collectPlaylistItems($gallery, $html);
        }
    }

    private function collectChannelItems(array $gallery): array
    {
        $items = [];
        foreach ($gallery['video'] as $video) {
            $uri = $video['contentUrl'] ?? null;
            if (!$uri || isset($items[$uri])) {
                continue;
            }
            $items[$uri] = $this->buildItem($video, $uri);
        }

        usort($items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $items;
    }

    private function collectPlaylistItems(array $gallery, string $html): array
    {
        preg_match_all('#<a[^>]*href="([^"]+)"[^>]*data-video-thumb="true"#', $html, $matches);
        $hrefs = array_values(array_unique($matches[1]));

        $items = [];
        foreach ($gallery['video'] as $i => $video) {
            if (!isset($hrefs[$i])) {
                continue;
            }
            $uri = urljoin(self::URI, $hrefs[$i]);
            if (isset($items[$uri])) {
                continue;
            }
            $items[$uri] = $this->buildItem($video, $uri);
        }

        return array_values($items);
    }

    private function buildItem(array $video, string $uri): array
    {
        $title = $video['name'] ?? 'Untitled';
        $thumbnail = $video['thumbnailUrl'] ?? null;

        $content = '';
        if ($thumbnail) {
            $content .= '<p><a href="' . e($uri) . '"><img src="' . e($thumbnail) . '"></a></p>';
        }
        if (!empty($video['description'])) {
            $content .= '<p>' . $video['description'] . '</p>';
        }

        return [
            'uri' => $uri,
            'title' => $title,
            'timestamp' => isset($video['uploadDate']) ? strtotime($video['uploadDate']) : null,
            'content' => $content ?: e($title),
        ];
    }

    private function fetchHtml(string $url): string
    {
        $html = getContents($url, [], [CURLOPT_USERAGENT => self::USER_AGENT]);
        if (!preg_match('//u', $html)) {
            $html = iconv('windows-1251', 'UTF-8//IGNORE', $html);
        }
        return $html;
    }

    private function extractVideoGallery(string $html): ?array
    {
        if (!preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches)) {
            return null;
        }
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true);
            if (($data['@type'] ?? null) === 'VideoGallery' && !empty($data['video'])) {
                return $data;
            }
        }
        return null;
    }

    private function extractPageTitle(string $html): ?string
    {
        if (preg_match('#<h1[^>]*>([^<]+)#', $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }
        return null;
    }

    private function extractChannelHandle(string $input): string
    {
        if (preg_match('#vkvideo\.ru/(@[\w.]+)#', $input, $matches)) {
            return $matches[1];
        }
        $input = trim($input);
        return str_starts_with($input, '@') ? $input : '@' . $input;
    }

    private function extractPlaylistId(string $input): string
    {
        if (preg_match('#vkvideo\.ru/playlist/([\d_-]+)#', $input, $matches)) {
            return $matches[1];
        }
        return trim($input);
    }
}
