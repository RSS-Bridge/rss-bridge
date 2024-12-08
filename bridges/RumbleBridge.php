<?php

class RumbleBridge extends BridgeAbstract
{
    const NAME = 'Rumble.com Bridge';
    const URI = 'https://rumble.com/';
    const DESCRIPTION = 'Fetches the latest channel/user videos and livestreams.';
    const MAINTAINER = 'dvikan, NotsoanoNimus';
    const CACHE_TIMEOUT = 60 * 60; // 1h
    const PARAMETERS = [
        [
            'account' => [
                'name' => 'Account',
                'type' => 'text',
                'required' => true,
                'title' => 'Name of the target account to create into a feed.',
                'defaultValue' => 'bjornandreasbullhansen',
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
        ]
    ];

    public function collectData()
    {
        $account = $this->getInput('account');
        $type = $this->getInput('type');
        $url = self::getURI();

        if (!preg_match('#^[\w\-_.@]+$#', $account) || strlen($account) > 64) {
            throw new \Exception('Invalid target account.');
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
                // Shouldn't ever happen.
                throw new \Exception('Invalid media type.');
        }

        $dom = getSimpleHTMLDOM($url);
        foreach ($dom->find('ol.thumbnail__grid div.thumbnail__grid--item') as $video) {
            $itemUrlString = self::URI . $video->find('a', 0)->href;
            $itemUrl = Url::fromString($itemUrlString);

            $item = [
                'title'     => $video->find('h3', 0)->plaintext,

                // Remove tracking parameter in query string
                'uri'       => $itemUrl->withQueryString(null)->__toString(),

                'author'    => $account . '@rumble.com',
                'content'   => defaultLinkTo($video, self::URI)->innertext,
            ];

            $time = $video->find('time', 0);
            if ($time) {
                $publishedAt = new \DateTimeImmutable($time->getAttribute('datetime'));
                $item['timestamp'] = $publishedAt->getTimestamp();
            }
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if ($this->getInput('account')) {
            return 'Rumble.com - ' . $this->getInput('account');
        }
        return self::NAME;
    }
}
