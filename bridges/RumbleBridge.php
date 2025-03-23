<?php

class RumbleBridge extends BridgeAbstract
{
    const NAME = 'Rumble.com Bridge';
    const URI = 'https://rumble.com/';
    const DESCRIPTION = 'Fetches detailed channel/user videos and livestreams from Rumble.';
    const MAINTAINER = 'dvikan, NotsoanoNimus';
    const CACHE_TIMEOUT = 0;

    const PARAMETERS = [
        [
            'account' => [
                'name' => 'Account',
                'type' => 'text',
                'required' => true,
                'title' => 'Name of the target account (e.g., 21UhrBitcoinPodcast)',
            ],
            'type' => [
                'name' => 'Account Type',
                'type' => 'list',
                'title' => 'The type of profile to create a feed from.',
                'values' => [
                    'Channel (All)' => 'channel',
                    'Channel Videos' => 'channel-videos',
                    'Channel Livestreams' => 'channel-livestream',
                    'User (All)' => 'user',
                ],
            ],
            'cache_timeout' => [
                'name' => 'Cache Timeout (seconds)',
                'type' => 'number',
                'defaultValue' => 0,
                'title' => 'How long to cache the feed (0 for no caching)',
            ],
        ]
    ];

    public function collectData()
    {
        $account = $this->getInput('account');
        $type = $this->getInput('type');
        $url = self::URI;

        if (!preg_match('#^[\w\-_.@]+$#', $account) || strlen($account) > 64) {
            returnServerError('Invalid target account.');
        }

        switch ($type) {
            case 'user':
                $url .= "user/$account";
                break;
            case 'channel':
                $url .= "c/$account";
                break;
            case 'channel-videos':
                $url .= "c/$account/videos";
                break;
            case 'channel-livestream':
                $url .= "c/$account/livestreams";
                break;
            default:
                returnServerError('Invalid media type.');
        }

        $html = $this->getContents($url);
        if (!$html) {
            returnServerError("Failed to fetch $url");
        }

        $items = [];
        if (preg_match('/<script.*?application\/ld\+json.*?>(.*?)<\/script>/s', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $videos = isset($jsonData['@graph']) ? $jsonData['@graph'] : [$jsonData];
                foreach ($videos as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'VideoObject') {
                        $items[] = $this->createItemFromJsonLd($item, $account);
                    }
                }
            }
        }

        if (empty($items)) {
            $dom = $this->getSimpleHTMLDOM($url);
            if ($dom) {
                foreach ($dom->find('ol.thumbnail__grid div.thumbnail__grid--item') as $video) {
                    $items[] = $this->createItemFromHtml($video, $account);
                }
            } else {
                returnServerError("Failed to parse HTML from $url");
            }
        }

        $this->items = $items;
    }

    private function createItemFromJsonLd(array $json, string $account): array
    {
        $item = [
            'title' => html_entity_decode($json['name'] ?? 'Untitled', ENT_QUOTES, 'UTF-8'),
            'author' => $account . '@rumble.com',
            'uri' => $json['url'] ?? '',
            'timestamp' => (new DateTime($json['uploadDate'] ?? 'now'))->getTimestamp(),
            'content' => '',
        ];

        if (isset($json['embedUrl'])) {
            $item['content'] .= "<iframe src='{$json['embedUrl']}' frameborder='0' allowfullscreen></iframe>";
        }

        if (isset($json['description'])) {
            $item['content'] .= '<p>' . html_entity_decode($json['description'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if (isset($json['thumbnailUrl'])) {
            $item['enclosures'] = [$json['thumbnailUrl']];
        }

        if (isset($json['duration'])) {
            $item['content'] .= "<p>Duration: {$json['duration']}</p>";
            $item['itunes:duration'] = $this->parseDurationToSeconds($json['duration']);
        }

        return $item;
    }

    private function createItemFromHtml($video, string $account): array
    {
        $href = $video->find('a', 0)->href ?? '';
        $item = [
            'title' => $video->find('h3', 0)->plaintext ?? 'Untitled',
            'author' => $account . '@rumble.com',
            'content' => $this->defaultLinkTo($video->innertext, self::URI),
            'uri' => self::URI . ltrim($href, '/'),
        ];

        $time = $video->find('time', 0);
        if ($time) {
            $item['timestamp'] = (new DateTime($time->getAttribute('datetime')))->getTimestamp();
        }

        return $item;
    }

    private function parseDurationToSeconds(string $duration): string
    {
        if (preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches)) {
            $hours = (int) str_replace('H', '', $matches[1] ?? 0);
            $minutes = (int) str_replace('M', '', $matches[2] ?? 0);
            $seconds = (int) str_replace('S', '', $matches[3] ?? 0);
            return (string) ($hours * 3600 + $minutes * 60 + $seconds);
        }
        return $duration;
    }

    public function getName()
    {
        return $this->getInput('account') ? "Rumble.com - {$this->getInput('account')}" : self::NAME;
    }

    public function getCacheTimeout()
    {
        return (int) $this->getInput('cache_timeout') ?: self::CACHE_TIMEOUT;
    }
}
