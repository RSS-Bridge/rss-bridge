<?php

class ManyVidsBridge extends BridgeAbstract
{
    const NAME = 'MANYVIDS';
    const URI = 'https://www.manyvids.com';
    const DESCRIPTION = 'Fetches the latest posts from a profile';
    const MAINTAINER = 'dvikan';
    const CACHE_TIMEOUT = 60 * 60;
    const PARAMETERS = [
        [
            'profile' => [
                'name' => 'Profile',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '678459/Aziani-Studios',
                'title' => 'id/profile or url',
            ],
        ]
    ];

    public function collectData()
    {
        $profile = $this->getInput('profile');
        if (preg_match('#^(\d+/.*)$#', $profile, $m)) {
            $profile = $m[1];
        } elseif (preg_match('#https://www.manyvids.com/Profile/(\d+/\w+)#', $profile, $m)) {
            $profile = $m[1];
        } else {
            throw new \Exception('nope');
        }
        $url = sprintf('https://www.manyvids.com/Profile/%s/Store/Videos/', $profile);
        $dom = getSimpleHTMLDOM($url);
        $videos = $dom->find('div[class^="ProfileTabGrid_card"]');
        foreach ($videos as $item) {
            $a = $item->find('a', 1);
            $uri = 'https://www.manyvids.com' . $a->href;
            if (preg_match('#Video/(\d+)/#', $uri, $m)) {
                $uid = 'manyvids/' . $m[1];
            }
            $this->items[] = [
                'title'     => $a->plaintext,
                'uri'       => $uri,
                'uid'       => $uid ?? $uri,
                'content'   => $item->innertext,
            ];
        }
    }
}
