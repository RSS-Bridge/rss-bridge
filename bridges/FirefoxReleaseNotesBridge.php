<?php

class FirefoxReleaseNotesBridge extends BridgeAbstract
{
    const NAME          = 'Firefox Release Notes';
    const URI           = 'https://www.mozilla.org';
    const DESCRIPTION   = 'Retrieve the latest Firefox release notes';
    const MAINTAINER    = 'tillcash';
    const CACHE_TIMEOUT = 3600; // 1 hour

    const PARAMETERS = [
        [
            'platform' => [
                'name' => 'platform',
                'type' => 'list',
                'values' => [
                    'Desktop' => 'desktop',
                    'Beta'    => 'beta',
                    'Nightly' => 'nightly',
                    'Android' => 'android',
                    'iOS'     => 'ios',
                ],
            ],
        ],
    ];

    public function getName()
    {
        $platform = $this->getKey('platform');

        if ($platform) {
            return sprintf('Firefox %s Release Notes', $platform);
        }

        return self::NAME;
    }

    public function collectData()
    {
        $platform = $this->getInput('platform');

        $url = self::URI . '/en-US/firefox/';
        $url .= $platform === 'desktop' ? 'notes/' : $platform . '/notes/';

        $html = getContents(
            $url,
            [],
            [CURLOPT_FOLLOWLOCATION => true]
        );

        $dom = str_get_html($html);

        $version = trim($dom->find('.c-release-version', 0)->plaintext);
        $content = trim($dom->find('.c-release-notes', 0)->innertext);
        $date    = trim($dom->find('.c-release-date', 0)->plaintext);

        $title = 'Firefox '
        . ($platform !== 'desktop' ? ucfirst($platform) . ' ' : '')
        . $version;

        $this->items[] = [
            'title'     => $title,
            'uri'       => $url,
            'uid'       => $platform . $version,
            'timestamp' => strtotime($date),
            'content'   => $content,
        ];
    }
}
