<?php

class FirefoxReleaseNotesBridge extends BridgeAbstract
{
    const NAME = 'Firefox Release Notes';
    const URI = 'https://www.firefox.com/en-US/firefox/';
    const DESCRIPTION = 'Retrieve the latest Firefox release notes.';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'platform' => [
                'name' => 'Platform',
                'type' => 'list',
                'values' => [
                    'Desktop' => '',
                    'Enterprise' => 'enterprise',
                    'Beta' => 'beta',
                    'Nightly' => 'nightly',
                    'Android' => 'android',
                    'iOS' => 'ios',
                ]
            ]
        ]
    ];

    public function getName()
    {
        $platform = $this->getKey('platform');
        return sprintf('Firefox %s Release Notes', $platform ?? '');
    }

    public function collectData()
    {
        $platform = $this->getKey('platform');
        $input = $this->getInput('platform');
        $url = self::URI . ($input ? $input . '/' : '') . 'notes/';
        try {
            getContents($url, [], [CURLOPT_FOLLOWLOCATION => false]);
            throw new \Exception('Expected a redirect from ' . $url);
        } catch (HttpException $e) {
            $location = $e->response->getHeader('location');
            if (!preg_match('#^/en-US/firefox/(android/|ios/|enterprise/)?\d+\.[\w.]+/releasenotes/$#', $location ?? '')) {
                throw $e;
            }
        }
        $url = 'https://www.firefox.com' . $location;
        $dom = getSimpleHTMLDOM($url);

        $version = $dom->find('.c-release-version', 0)->innertext;

        $this->items[] = [
            'content' => $dom->find('.c-release-notes', 0)->innertext,
            'timestamp' => $dom->find('.c-release-date', 0)->innertext,
            'title' => sprintf('Firefox %s %s Release Note', $platform, $version),
            'uri' => $url,
            'uid' => $platform . $version,
        ];
    }
}
