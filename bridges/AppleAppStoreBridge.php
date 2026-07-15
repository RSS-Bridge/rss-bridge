<?php

class AppleAppStoreBridge extends BridgeAbstract
{
    const MAINTAINER = 'NohamR';
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
        // 'p' => [
        //     'name'  => 'Platform',
        //     'type'  => 'list',
        //     'values'    => [
        //         'iPad'  => 'ipad',
        //         'iPhone'    => 'iphone',
        //         'Mac'   => 'mac',
        //         'Web'   => 'web',
        //         'Apple TV'  => 'appletv',
        //     ],
        //     'defaultValue'  => 'mac',
        // ],
        # The platform parameter is currently not used in the URL construction,
        # but it may be useful for future enhancements or for filtering version
        # history based on platform-specific releases.
        'p' => [
            'name'  => 'Platform',
            'type'  => 'list',
            'values'    => [
                'Mac'   => 'mac',
            ],
            'defaultValue'  => 'mac',
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

    private $name;

    private function makeHtmlUrl()
    {
        $id = $this->getInput('id');
        $country = strtolower($this->getInput('country'));
        return sprintf('https://apps.apple.com/%s/app/id%s', $country, $id);
    }

    private function getHtmlRequestHeaders()
    {
        return [
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'cache-control: no-cache',
            'pragma: no-cache',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
        ];
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

    private function getAppData()
    {
        $url = $this->makeHtmlUrl();
        $this->debugLog(sprintf('Fetching HTML page for serialized data extraction: %s', $url));
        $content = getContents($url, $this->getHtmlRequestHeaders());

        $matches = [];
        if (!preg_match('#<script[^>]*id="serialized-server-data"[^>]*>(.*?)</script>#s', $content, $matches)) {
            throw new \Exception('Failed to locate serialized server data in HTML page');
        }

        $serializedServerData = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);

        try {
            $json = Json::decode($serializedServerData);
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Failed to parse serialized server data: %s', $e->getMessage()));
        }

        if (!isset($json['data']) || empty($json['data'])) {
            throw new \Exception('No app data found in serialized server data');
        }

        $this->debugLog('Successfully retrieved app data from HTML page');
        return $json['data'][0]['data'] ?? $json['data'][0];
    }

    private function extractAppDetails($data)
    {
        if (isset($data['lockup'])) {
            $this->name = $data['lockup']['title'] ?? null;
            $author = $data['developerAction']['title'] ?? ($data['lockup']['developerTagline'] ?? null);
            $this->debugLog(sprintf('Found app details in lockup: %s by %s', $this->name, $author));
            return [$this->name, $author];
        }

        if (isset($data['title'])) {
            $this->name = $data['title'];
            $author = $data['developerAction']['title'] ?? ($data['lockup']['developerTagline'] ?? null);
            $this->debugLog(sprintf('Found app details in title: %s by %s', $this->name, $author));
            return [$this->name, $author];
        }

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
        $this->debugLog('Extracting version history from serialized server data');

        $version_history = [];

        $pageData = $data['shelfMapping']['mostRecentVersion']['seeAllAction']['pageData'] ?? [];
        $shelves = $pageData['shelves'] ?? [];

        foreach ($shelves as $shelf) {
            foreach (($shelf['items'] ?? []) as $entry) {
                if (($entry['$kind'] ?? null) !== 'TitledParagraph') {
                    continue;
                }

                $version_history[] = [
                    'versionDisplay' => $entry['primarySubtitle'] ?? 'Unknown Version',
                    'releaseNotes' => $entry['text'] ?? 'No release notes available',
                    'releaseDate' => $entry['secondarySubtitle'] ?? null,
                ];
            }
        }

        if (empty($version_history)) {
            $this->debugLog('No version history found');
        }

        return $version_history;
    }

    public function collectData()
    {
        $this->debugLog('Getting app data');
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
            $item['timestamp'] = strtotime($release_date) ?: $release_date;
            $item['author'] = $author;
            $item['uri'] = $data['canonicalURL'] ?? $this->makeHtmlUrl();

            $this->items[] = $item;
        }

        $this->debugLog(sprintf('Successfully collected %d items', count($this->items)));
    }
}