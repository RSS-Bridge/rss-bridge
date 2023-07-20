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

    private $feedName = '';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $title = $html->find('h1', 0)->plaintext ?? self::NAME;
        $this->feedName = htmlspecialchars_decode($title);

        $SIGI_STATE_RAW = $html->find('script[id=SIGI_STATE]', 0)->innertext;
        $SIGI_STATE = json_decode($SIGI_STATE_RAW);

        foreach ($SIGI_STATE->ItemModule as $key => $value) {
            $item = [];

            $link = 'https://www.tiktok.com/@' . $value->author . '/video/' . $value->id;
            $image = $value->video->dynamicCover;
            if (empty($image)) {
                $image = $value->video->cover;
            }
            $views = $value->stats->playCount;

            $item['title'] = $value->desc;
            $item['uri'] = $link;
            $item['enclosures'][] = $image;
            $item['content'] = <<<EOD
<a href="{$link}"><img src="{$image}"/></a>
<p>{$views} views<p>
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
                return $this->feedName . ' (' . $this->processUsername() . ') - TikTok';
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
