<?php

class FirefoxReleaseNotesBridge extends BridgeAbstract
{
    const NAME = 'Firefox Release Notes';
    const URI = 'https://www.mozilla.org/en-US/firefox/';
    const DESCRIPTION = 'Retrieve the latest Firefox release notes.';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'platform' => [
                'name' => 'Platform',
                'type' => 'list',
                'values' => [
                    'Desktop' => '',
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
        $url = self::URI . $this->getInput('platform') . '/notes/';
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
