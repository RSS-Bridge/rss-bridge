<?php

class FirefoxReleaseNotesBridge extends BridgeAbstract
{
    const NAME = 'Firefox Release Notes';
    const URI = 'https://www.mozilla.org/en-US/firefox/';
    const DESCRIPTION = 'Retrieve the latest Firefox release notes';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'platform' => [
                'name' => 'Platform',
                'type' => 'list',
                'values' => [
                    'desktop' => '',
                    'beta' => 'beta',
                    'nightly' => 'nightly',
                    'android' => 'android',
                    'ios' => 'ios',
                ]
            ]
        ]
    ];

    public function getName()
    {
        return $this->getKey('platform')
            ? sprintf('Firefox %s release note', $this->getKey('platform'))
            : self::NAME;
    }

    public function collectData()
    {
        $platform = $this->getInput('platform');
        $key = $this->getKey('platform');
        $url = self::URI . $platform . '/notes/';
        $html = getSimpleHTMLDOM($url);
        $version = $html->find('.c-release-version', 0)->innertext;
        $this->items[] = [
            'content' => $html->find('.c-release-notes', 0)->innertext,
            'timestamp' => $html->find('.c-release-date', 0)->innertext,
            'title' => sprintf('Firefox %s %s release note', $key, $version),
            'uri' => $url,
            'uid' => $key . $version,
        ];
    }
}