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
        foreach ($dom->find('li.video-listing-entry') as $video) {
            $this->items[] = [
                'title'     => $video->find('h3', 0)->plaintext,
                'uri'       => self::URI . $video->find('a', 0)->href,
                'author'    => $account . '@rumble.com',
                'content'   => defaultLinkTo($video, self::URI)->innertext,
            ];
        }
    }

    public function getName()
    {
        return 'Rumble.com ' . $this->getInput('account');
    }
}
