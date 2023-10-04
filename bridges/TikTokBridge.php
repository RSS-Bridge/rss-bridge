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

        $var = $html->find('script[id=SIGI_STATE]', 0);
        if (!$var) {
            throw new \Exception('Unable to find tiktok user data for ' . $this->processUsername());
        }
        $SIGI_STATE_RAW = $var->innertext;
        $SIGI_STATE = Json::decode($SIGI_STATE_RAW, false);

        if (!isset($SIGI_STATE->ItemModule)) {
            return;
        }

        foreach ($SIGI_STATE->ItemModule as $key => $value) {
            $item = [];

            $link = 'https://www.tiktok.com/@' . $value->author . '/video/' . $value->id;
            $image = $value->video->dynamicCover;
            if (empty($image)) {
                $image = $value->video->cover;
            }
            $views = $value->stats->playCount;
            $hastags = [];
            foreach ($value->textExtra as $tag) {
                $hastags[] = $tag->hashtagName;
            }
            $hastags_str = '';
            foreach ($hastags as $tag) {
                $hastags_str .= '<a href="https://www.tiktok.com/tag/' . $tag . '">#' . $tag . '</a> ';
            }

            $item['uri'] = $link;
            $item['title'] = $value->desc;
            $item['timestamp'] = $value->createTime;
            $item['author'] = '@' . $value->author;
            $item['enclosures'][] = $image;
            $item['categories'] = $hastags;
            $item['content'] = <<<EOD
<a href="{$link}"><img src="{$image}"/></a>
<p>{$views} views<p><br/>Hashtags: {$hastags_str}
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
