<?php

class FurAffinityUserBridge extends BridgeAbstract
{
    const NAME = 'FurAffinity User Gallery';
    const URI = 'https://www.furaffinity.net';
    const MAINTAINER = 'CyberJacob';
    const DESCRIPTION = 'See https://rss-bridge.github.io/rss-bridge/Bridge_Specific/Furaffinityuser.html for explanation';
    const PARAMETERS = [
        [
            'searchUsername' => [
                'name' => 'Search Username',
                'type' => 'text',
                'required' => true,
                'title' => 'Username to fetch the gallery for',
                'exampleValue' => 'armundy',
            ],
            'aCookie' => [
                'name' => 'Login cookie \'a\'',
                'type' => 'text',
                'required' => true
            ],
            'bCookie' => [
                'name' => 'Login cookie \'b\'',
                'type' => 'text',
                'required' => true
            ]
        ]
    ];

    public function collectData()
    {
        $opt = [CURLOPT_COOKIE => 'b=' . $this->getInput('bCookie') . '; a=' . $this->getInput('aCookie')];

        $url = self::URI . '/gallery/' . $this->getInput('searchUsername');

        $html = getSimpleHTMLDOM($url, [], $opt)
            or returnServerError('Could not load the user\'s gallery page.');

        $submissions = $html->find('section[id=gallery-gallery]', 0)->find('figure');
        foreach ($submissions as $submission) {
            $item = [];
            $item['title'] = $submission->find('figcaption', 0)->find('a', 0)->plaintext;

            $thumbnail = $submission->find('a', 0);
            $thumbnail->href = self::URI . $thumbnail->href;

            $item['content'] = $submission->find('a', 0);

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        return self::NAME . ' for ' . $this->getInput('searchUsername');
    }

    public function getURI()
    {
        return self::URI . '/user/' . $this->getInput('searchUsername');
    }
}
