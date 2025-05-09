<?php

class TikTokBridge extends BridgeAbstract
{
    const NAME = 'TikTok Bridge';
    const URI = 'https://www.tiktok.com';
    const DESCRIPTION = 'Returns posts';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'By user' => [
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '@tiktok',
            ]
        ]];

    const TEST_DETECT_PARAMETERS = [
        'https://www.tiktok.com/@tiktok' => [
            'context' => 'By user',
            'username' => '@tiktok',
        ]
    ];

    const CACHE_TIMEOUT = 60 * 60; // 1h

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached('https://www.tiktok.com/embed/' . $this->processUsername());
        $authorProfilePicture = $html->find('img[data-e2e=creator-profile-userInfo-Avatar]', 0)->src ?? '';

        $videos = $html->find('div[data-e2e=common-videoList-VideoContainer]');

        foreach ($videos as $video) {
            $item = [];

            // Omit query string (remove tracking parameters)
            $a = $video->find('a', 0);
            $href = $a->href;
            $parsedUrl = parse_url($href);
            $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/' . ltrim($parsedUrl['path'], '/');

            $json = null;

            // Sometimes the API fails to return data for a second, so try a few times
            $attempts = 0;
            do {
                try {
                    // Fetch the video embed data from the OEmbed API
                    $json = getContents('https://www.tiktok.com/oembed?url=' . $url);
                } catch (HttpException $e) {
                    $attempts++;
                    // Sleep 0.1s
                    usleep(100000);
                    continue;
                }
                break;
            } while ($attempts < 3);

            if ($json) {
                $videoEmbedData = json_decode($json);
            } else {
                $videoEmbedData = new \stdClass();
                $videoEmbedData->title = $url;
                $videoEmbedData->thumbnail_url = '';
                $videoEmbedData->author_unique_id = '';
            }

            $title = $videoEmbedData->title;
            $image = $videoEmbedData->thumbnail_url;
            $views = $video->find('div[data-e2e=common-Video-Count]', 0)->plaintext;

            $enclosures = [$image, $authorProfilePicture];

            $item['uri'] = $url;
            $item['title'] = $title;
            $item['author'] = '@' . $videoEmbedData->author_unique_id;
            $item['enclosures'] = $enclosures;
            $item['content'] = <<<EOD
<p>$title</p>
<a href="{$url}"><img src="{$image}"/></a>
<p>{$views} views<p><br/>
EOD;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'By user':
                return self::URI . '/' . $this->processUsername();
            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By user':
                return  $this->processUsername() . ' - TikTok';
            default:
                return parent::getName();
        }
    }

    private function processUsername()
    {
        $username = trim($this->getInput('username'));
        if (preg_match('#^https?://www\.tiktok\.com/@(.*)$#', $username, $m)) {
            return '@' . $m[1];
        }
        if (substr($username, 0, 1) !== '@') {
            return '@' . $username;
        }
        return $username;
    }

    public function detectParameters($url)
    {
        if (preg_match('/tiktok\.com\/(@[\w]+)/', $url, $matches) > 0) {
            return [
                'context' => 'By user',
                'username' => $matches[1]
            ];
        }

        return null;
    }
}
