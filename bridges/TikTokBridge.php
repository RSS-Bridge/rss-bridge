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

        foreach ($html->find('div.tiktok-x6y88p-DivItemContainerV2') as $div) {
            $item = [];

            // todo: find proper link to tiktok item
            $link = $div->find('a', 0)->href;

            $image = $div->find('img', 0)->src ?? '';

            $views = $div->find('strong.video-count', 0)->plaintext;

            if ($link === 'https://www.tiktok.com/') {
                $link = $this->getURI();
            }
            $item['uri'] = $link;

            $a = $div->find('a', 1);
            if ($a) {
                $item['title'] = $a->plaintext;
            } else {
                $item['title'] = $this->getName();
            }
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
