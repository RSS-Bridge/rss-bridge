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
        return "https://apps.apple.com/{$country}/app/id{$id}";
    }

    private function makeJsonUrl()
    {
        $id = $this->getInput('id');
        $country = $this->getInput('country');
        $platform = $this->getInput('p');
        
        $platform_param = ($platform === 'mac') ? 'mac' : $platform;
        
        return "https://amp-api.apps.apple.com/v1/catalog/{$country}/apps/{$id}?platform={$platform_param}&extend=versionHistory";
    }

    public function getName()
    {
        if (isset($this->name)) {
            return $this->name . ' - AppStore Updates';
        }

        return parent::getName();
    }

    private function debugLog($message)
    {
        if ($this->getInput('debug')) {
            error_log("[AppleAppStoreBridge] $message");
        }
    }

    private function getHtml()
    {
        $url = $this->makeHtmlUrl();
        $this->debugLog("Fetching HTML from: $url");
        
        $html = getSimpleHTMLDOM($url);
        
        if (!$html) {
            throw new \Exception("Failed to retrieve HTML from App Store");
        }
        
        $this->debugLog("HTML fetch successful");
        return $html;
    }

    private function getJWTToken()
    {
        $html = $this->getHtml();
        $meta = $html->find('meta[name="web-experience-app/config/environment"]', 0);
        
        if (!$meta || !isset($meta->content)) {
            throw new \Exception("JWT token not found in page content");
        }
        
        try {
            $decoded_content = urldecode($meta->content);
            $this->debugLog("Found meta tag content");
            $decoded_json = json_decode($decoded_content, true);
            
            if (!isset($decoded_json['MEDIA_API']['token'])) {
                throw new \Exception("Token field not found in JSON structure");
            }
            
            $token = $decoded_json['MEDIA_API']['token'];
            $this->debugLog("Successfully extracted JWT token");
            return $token;
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract JWT token: " . $e->getMessage());
        }
    }

    private function getAppData()
    {
        $token = $this->getJWTToken();
        
        $url = $this->makeJsonUrl();
        $this->debugLog("Fetching data from API: $url");
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Origin: https://apps.apple.com',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        
        $content = getContents($url, $headers);
        if (!$content) {
            throw new \Exception("Failed to get content from API");
        }
        
        $json = json_decode($content, true);
        
        if (!isset($json['data']) || empty($json['data'])) {
            throw new \Exception("No app data found in API response");
        }
        
        $this->debugLog("Successfully retrieved app data from API");
        return $json['data'][0];
    }

    private function extractAppDetails($data)
    {
        try {
            if (isset($data['attributes'])) {
                $this->name = $data['attributes']['name'] ?? null;
                $author = $data['attributes']['artistName'] ?? null;
                $this->debugLog("Found app details in attributes: {$this->name} by {$author}");
                return [$this->name, $author];
            }
            
            $this->name = "App " . $this->getInput('id');
            $this->debugLog("App details not found, using default: {$this->name}");
            return [$this->name, "Unknown Developer"];
        } catch (\Exception $e) {
            $this->debugLog("Error extracting app details: " . $e->getMessage());
            $this->name = "App " . $this->getInput('id');
            return [$this->name, "Unknown Developer"];
        }
    }

    private function getVersionHistory($data)
    {
        $platform = $this->getInput('p');
        $this->debugLog("Extracting version history for platform: {$platform}");
        
        try {
            $platform_key = self::PLATFORM_MAPPING[$platform] ?? $platform;
            
            if (isset($data['attributes']['platformAttributes'][$platform_key]['versionHistory'])) {
                return $data['attributes']['platformAttributes'][$platform_key]['versionHistory'];
            }
            
            $this->debugLog("No version history found for {$platform}");
            return [];
            
        } catch (\Exception $e) {
            $this->debugLog("Error extracting version history: " . $e->getMessage());
            return [];
        }
    }

    public function collectData()
    {
        try {
            $this->debugLog("Getting data for " . $this->getInput('p') . " app");
            $data = $this->getAppData();
            
            list($name, $author) = $this->extractAppDetails($data);
            
            $version_history = $this->getVersionHistory($data);
            $this->debugLog("Found " . count($version_history) . " versions for {$name}");
            
            foreach ($version_history as $entry) {
                try {
                    $version = $entry['versionDisplay'] ?? 'Unknown Version';
                    $release_notes = $entry['releaseNotes'] ?? 'No release notes available';
                    $release_date = $entry['releaseDate'] ?? 'Unknown Date';
                    
                    $item = [];
                    $item['title'] = "{$name} - {$version}";
                    $item['content'] = nl2br($release_notes) ?: 'No release notes available';
                    $item['timestamp'] = $release_date;
                    $item['author'] = $author;
                    $item['uri'] = $this->makeHtmlUrl();
                    
                    $this->items[] = $item;
                } catch (\Exception $e) {
                    $this->debugLog("Error processing version entry: " . $e->getMessage());
                }
            }
            
            $this->debugLog("Successfully collected " . count($this->items) . " items");
        } catch (\Exception $e) {
            $this->debugLog("Error collecting data: " . $e->getMessage());
            throw $e;
        }
    }
}