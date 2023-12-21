<?php

class RumbleBridge extends BridgeAbstract
{
    const NAME = 'rumble.com bridge';
    const URI = 'https://rumble.com';
    const DESCRIPTION = 'Fetches the latest channel/user videos';
    const MAINTAINER = 'dvikan';
    const CACHE_TIMEOUT = 60 * 60; // 1h
    const PARAMETERS = [
        [
            'account' => [
                'name' => 'Account',
                'type' => 'text',
                'required' => true,
                'defaultValue' => 'bjornandreasbullhansen',
            ],
            'type' => [
                'type' => 'list',
                'name' => 'Type',
                'values' => [
                    'Channel' => 'channel',
                    'User' => 'user',
                ]
            ],
        ]
    ];

    public function collectData()
    {
        $account = $this->getInput('account');
        $type = $this->getInput('type');

        if ($type === 'channel') {
            $url = "https://rumble.com/c/$account";
        }
        if ($type === 'user') {
            $url = "https://rumble.com/user/$account";
        }

        $dom = getSimpleHTMLDOM($url);
        foreach ($dom->find('ol.thumbnail__grid div.thumbnail__grid--item') as $video) {
            $item = [
                'title'     => $video->find('h3', 0)->plaintext,
                'uri'       => self::URI . $video->find('a', 0)->href,
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
        return 'Rumble.com ' . $this->getInput('account');
    }
}
