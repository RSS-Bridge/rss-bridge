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
            ],
            'defaultValue'  => 'US',
        ],
    ]];

    const PLATFORM_MAPPING = [
        'iphone'    => 'ios',
        'ipad'  => 'ios',
    ];

    private function makeHtmlUrl($id, $country)
    {
        return 'https://apps.apple.com/' . $country . '/app/id' . $id;
    }

    private function makeJsonUrl($id, $platform, $country)
    {
        return "https://amp-api.apps.apple.com/v1/catalog/$country/apps/$id?platform=$platform&extend=versionHistory";
    }

    public function getName()
    {
        if (isset($this->name)) {
            return $this->name . ' - AppStore Updates';
        }

        return parent::getName();
    }

    /**
     * In case of some platforms, the data is present in the initial response
     */
    private function getDataFromShoebox($id, $platform, $country)
    {
        $uri = $this->makeHtmlUrl($id, $country);
        $html = getSimpleHTMLDOMCached($uri, 3600);
        $script = $html->find('script[id="shoebox-ember-data-store"]', 0);

        $json = json_decode($script->innertext, true);
        return $json['data'];
    }

    private function getJWTToken($id, $platform, $country)
    {
        $uri = $this->makeHtmlUrl($id, $country);

        $html = getSimpleHTMLDOMCached($uri, 3600);

        $meta = $html->find('meta[name="web-experience-app/config/environment"]', 0);

        $json = urldecode($meta->content);

        $json = json_decode($json);

        return $json->MEDIA_API->token;
    }

    private function getAppData($id, $platform, $country, $token)
    {
        $uri = $this->makeJsonUrl($id, $platform, $country);

        $headers = [
            "Authorization: Bearer $token",
            'Origin: https://apps.apple.com',
        ];

        $json = json_decode(getContents($uri, $headers), true);

        return $json['data'][0];
    }

    /**
     * Parses the version history from the data received
     * @return array list of versions with details on each element
     */
    private function getVersionHistory($data, $platform)
    {
        switch ($platform) {
            case 'mac':
                return $data['relationships']['platforms']['data'][0]['attributes']['versionHistory'];
            default:
                $os = self::PLATFORM_MAPPING[$platform];
                return $data['attributes']['platformAttributes'][$os]['versionHistory'];
        }
    }

    public function collectData()
    {
        $id = $this->getInput('id');
        $country = $this->getInput('country');
        $platform = $this->getInput('p');

        switch ($platform) {
            case 'mac':
                $data = $this->getDataFromShoebox($id, $platform, $country);
                break;

            default:
                $token = $this->getJWTToken($id, $platform, $country);
                $data = $this->getAppData($id, $platform, $country, $token);
        }

        $versionHistory = $this->getVersionHistory($data, $platform);
        $name = $this->name = $data['attributes']['name'];
        $author = $data['attributes']['artistName'];

        foreach ($versionHistory as $row) {
            $item = [];

            $item['content'] = nl2br($row['releaseNotes']);
            $item['title'] = $name . ' - ' . $row['versionDisplay'];
            $item['timestamp'] = $row['releaseDate'];
            $item['author'] = $author;

            $item['uri'] = $this->makeHtmlUrl($id, $country);

            $this->items[] = $item;
        }
    }
}
