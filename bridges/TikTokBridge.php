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

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $title = $html->find('h1', 0)->plaintext ?? self::NAME;
        $this->feedName = htmlspecialchars_decode($title);

        foreach ($html->find('div.tiktok-x6y88p-DivItemContainerV2') as $div) {
            $item = [];

            $link = $div->find('a', 0)->href;
            $image = $div->find('img', 0)->src;
            $views = $div->find('strong.video-count', 0)->plaintext;

            $item['uri'] = $link;
            $item['title'] = $div->find('a', 1)->plaintext;
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
        if (substr($this->getInput('username'), 0, 1) !== '@') {
            return '@' . $this->getInput('username');
        }

        return $this->getInput('username');
    }
}
