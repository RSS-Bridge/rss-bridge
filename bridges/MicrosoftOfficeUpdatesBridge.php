<?php

declare(strict_types=1);

class MicrosoftOfficeUpdatesBridge extends BridgeAbstract
{
    public const NAME = 'Microsoft Office Updates';
    public const URI = 'https://learn.microsoft.com/en-us/officeupdates/';
    public const DESCRIPTION = 'Returns the latest release notes for Microsoft 365 update channels';
    public const CACHE_TIMEOUT = 21600; // 6 hours
    public const MAINTAINER = 'tillcash';

    public const PARAMETERS = [
        [
            'channel' => [
                'name' => 'Update Channel',
                'type' => 'list',
                'values' => [
                    'Current' => 'current-channel',
                    'Monthly' => 'monthly-enterprise-channel',
                    'Semi-Annual' => 'semi-annual-enterprise-channel',
                ],
            ],
        ],
    ];

    public function getIcon()
    {
        return 'https://learn.microsoft.com/favicon.ico';
    }

    public function getName()
    {
        $channel = $this->getKey('channel');
        return self::NAME . ($channel ? ': ' . $channel : '');
    }

    public function collectData(): void
    {
        $path = $this->getInput('channel') ?? 'current-channel';
        $url = self::URI . $path;

        $dom = getSimpleHTMLDOMCached($url, self::CACHE_TIMEOUT);
        if (!$dom) {
            throwServerException('Invalid or empty content received');
        }

        $dom = defaultLinkTo($dom, self::URI);
        $versions = $dom->find('h2[id^="version-"]');

        foreach ($versions as $version) {
            $this->items[] = [
                'title'   => trim($version->plaintext),
                'uri'     => $url . '#' . $version->id,
                'uid'     => $version->id,
                'content' => $this->collectContent($version),
            ];
        }
    }

    private function collectContent($version): string
    {
        $content = '';
        $sibling = $version->next_sibling();

        while ($sibling) {
            if ($sibling->tag === 'h2') {
                break;
            }

            $content .= $sibling->outertext;
            $sibling = $sibling->next_sibling();
        }

        return trim($content);
    }
}
