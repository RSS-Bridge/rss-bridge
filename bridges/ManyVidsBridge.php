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
        $el = $dom->find('section[id="app-store-videos"]', 0);
        $json = $el->getAttribute('data-store-videos');
        $json = html_entity_decode($json);
        $data = Json::decode($json, false);
        foreach ($data->content->items as $item) {
            $this->items[] = [
                'title' => $item->title,
                'uri' => 'https://www.manyvids.com' . $item->preview->path,
                'uid' => 'manyvids/' . $item->id,
                'content' => sprintf('<img src="%s">', $item->videoThumb),
            ];
        }
    }
}
