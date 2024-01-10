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
            'context' => 'By user', 'username' => '@tiktok'
        ]
    ];

    const CACHE_TIMEOUT = 900; // 15 minutes

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached('https://www.tiktok.com/embed/' . $this->processUsername());

        $author = $html->find('span[data-e2e=creator-profile-userInfo-TUXText]', 0)->plaintext ?? self::NAME;

        $videos = $html->find('div[data-e2e=common-videoList-VideoContainer]');

        foreach ($videos as $video) {
            $item = [];

            // Omit query string (remove tracking parameters)
            $a = $video->find('a', 0);
            $href = $a->href;
            $parsedUrl = parse_url($href);
            $url = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/' . ltrim($parsedUrl['path'], '/');

            $image = $video->find('video', 0)->poster;
            $views = $video->find('div[data-e2e=common-Video-Count]', 0)->plaintext;

            $enclosures = [$image];

            $item['uri'] = $url;
            $item['title'] = 'Video';
            $item['author'] = '@' . $author;
            $item['enclosures'] = $enclosures;
            $item['content'] = <<<EOD
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
