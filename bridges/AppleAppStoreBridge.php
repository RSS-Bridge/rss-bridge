<?php

class AppleAppStoreBridge extends BridgeAbstract
{
    const MAINTAINER = 'captn3m0';
    const NAME = 'Apple App Store';
    const URI = 'https://apps.apple.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns version updates for a specific application';

    const PARAMETERS = [[
        'id' => [
            'name'  => 'Application ID',
            'required'  => true,
            'exampleValue'  => '310633997'
        ],
        'p' => [
            'name'  => 'Platform',
            'type'  => 'list',
            'values'    => [
                'iPad'  => 'ipad',
                'iPhone'    => 'iphone',
                'Mac'   => 'mac',

                // The following 2 are present in responses
                // but not yet tested
                'Web'   => 'web',
                'Apple TV'  => 'appletv',
            ],
            'defaultValue'  => 'iphone',
        ],
        'country'   => [
            'name'  => 'Store Country',
            'type'  => 'list',
            'values'    => [
                'US'    => 'US',
                'India' => 'IN',
                'Canada' => 'CA',
                'Germany' => 'DE',
                'Netherlands' => 'NL',
                'Belgium (NL)' => 'BENL',
                'Belgium (FR)' => 'BEFR',
                'France' => 'FR',
                'Italy' => 'IT',
                'United Kingdom' => 'UK',
                'Spain' => 'ES',
                'Portugal' => 'PT',
                'Australia' => 'AU',
                'New Zealand' => 'NZ',
                'Indonesia' => 'ID',
                'Brazil' => 'BR',
            ],
            'defaultValue'  => 'US',
        ],
        'debug' => [
            'name' => 'Debug Mode',
            'type' => 'checkbox',
            'defaultValue' => false
        ]
    ]];

    const PLATFORM_MAPPING = [
        'iphone' => 'ios',
        'ipad' => 'ios',
        'mac' => 'osx'
    ];

    private $name;

    private function makeHtmlUrl()
    {
        $id = $this->getInput('id');
        $country = $this->getInput('country');
        return sprintf('https://apps.apple.com/%s/app/id%s', $country, $id);
    }

    private function makeJsonUrl()
    {
        $id = $this->getInput('id');
        $country = $this->getInput('country');
        $platform = $this->getInput('p');

        $platform_param = ($platform === 'mac') ? 'mac' : $platform;

        return sprintf(
            'https://amp-api-edge.apps.apple.com/v1/catalog/%s/apps/%s?platform=%s&extend=versionHistory',
            $country,
            $id,
            $platform_param
        );
    }

    public function getName()
    {
        if (isset($this->name)) {
            return sprintf('%s - AppStore Updates', $this->name);
        }

        return parent::getName();
    }

    private function debugLog($message)
    {
        if ($this->getInput('debug')) {
            $this->logger->info(sprintf('[AppleAppStoreBridge] %s', $message));
        }
    }

    private function getHtml()
    {
        $url = $this->makeHtmlUrl();
        $this->debugLog(sprintf('Fetching HTML from: %s', $url));

        return getSimpleHTMLDOM($url);
    }

    private function getAppData()
    {
        // Spoof a call to get the HTML first to mimic browser behavior
        $url = $this->makeHtmlUrl();
        $content = getContents($url);

        // The above method stopped working, using a hardcoded token for now, "exp": 1769466135 (~Jan 26 2026)
        // This token is hardcoded in Apple's own JavaScript source code: https://apps.apple.com/assets/index~BMeKnrDH8T.js

        // phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
        $token = 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IlU4UlRZVjVaRFMifQ.'
            . 'eyJpc3MiOiI3TktaMlZQNDhaIiwiaWF0IjoxNzYyOTkwMTA3LCJleHAiOjE3NzAyNDc3MDcsInJvb3RfaHR0cHNfb3JpZ2luIjpbImFwcGxlLmNvbSJdfQ.'
            . 'IrZxlIHsZBiBLZPw1UZYkyqwbPDPmzcj8U57M3w252i3A4TRzASKx2aGAoXJ0WtuNihmyyopREeVqpJlpjq0fw';
        // phpcs:enable Generic.Strings.UnnecessaryStringConcat.Found

        $url = $this->makeJsonUrl();
        $this->debugLog(sprintf('Fetching data from API: %s', $url));

        $headers = [
            'accept: */*',
            'Authorization: Bearer ' . $token,
            'cache-control: no-cache',
            'Origin: https://apps.apple.com',
            'Referer: https://apps.apple.com/',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36w',
        ];

        $content = getContents($url, $headers);

        try {
            $json = Json::decode($content);
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Failed to parse API response: %s', $e->getMessage()));
        }

        if (!isset($json['data']) || empty($json['data'])) {
            throw new \Exception('No app data found in API response');
        }

        $this->debugLog('Successfully retrieved app data from API');
        return $json['data'][0];
    }

    private function extractAppDetails($data)
    {
        if (isset($data['attributes'])) {
            $this->name = $data['attributes']['name'] ?? null;
            $author = $data['attributes']['artistName'] ?? null;
            $this->debugLog(sprintf('Found app details in attributes: %s by %s', $this->name, $author));
            return [$this->name, $author];
        }

        // Fallback to default values if not found
        $this->name = sprintf('App %s', $this->getInput('id'));
        $this->debugLog(sprintf('App details not found, using default: %s', $this->name));
        return [$this->name, 'Unknown Developer'];
    }

    private function getVersionHistory($data)
    {
        $platform = $this->getInput('p');
        $this->debugLog(sprintf('Extracting version history for platform: %s', $platform));

        // Get the mapped platform key (ios for iPhone/iPad, osx for Mac)
        $platform_key = self::PLATFORM_MAPPING[$platform] ?? $platform;

        $version_history = $data['attributes']['platformAttributes'][$platform_key]['versionHistory'] ?? [];

        if (empty($version_history)) {
            $this->debugLog(sprintf('No version history found for %s', $platform));
        }

        return $version_history;
    }

    public function collectData()
    {
        $this->debugLog(sprintf('Getting data for %s app', $this->getInput('p')));
        $data = $this->getAppData();

        // Get app name and author using array destructuring
        [$name, $author] = $this->extractAppDetails($data);

        // Get version history
        $version_history = $this->getVersionHistory($data);
        $this->debugLog(sprintf('Found %d versions for %s', count($version_history), $name));

        foreach ($version_history as $entry) {
            $version = $entry['versionDisplay'] ?? 'Unknown Version';
            $release_notes = $entry['releaseNotes'] ?? 'No release notes available';
            $release_date = $entry['releaseDate'] ?? 'Unknown Date';

            $item = [];
            $item['title'] = sprintf('%s - %s', $name, $version);
            $item['content'] = nl2br($release_notes) ?: 'No release notes available';
            $item['timestamp'] = $release_date;
            $item['author'] = $author;
            $item['uri'] = $this->makeHtmlUrl();

            $this->items[] = $item;
        }

        $this->debugLog(sprintf('Successfully collected %d items', count($this->items)));
    }
}