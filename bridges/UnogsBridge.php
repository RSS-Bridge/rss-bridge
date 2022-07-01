<?php

class UnogsBridge extends BridgeAbstract
{
    const MAINTAINER = 'csisoap';
    const NAME = 'uNoGS Bridge';
    const URI = 'https://unogs.com';
    const DESCRIPTION = 'Return what\'s new or removal on Netflix';

    const PARAMETERS = [
        'global' => [
            'feed' => [
                'name' => 'feed',
                'type' => 'list',
                'title' => 'Choose whether you want latest movies or removal on Netflix',
                'values' => [
                    'What\'s New' => 'new last 7 days',
                    'Expiring' => 'expiring'
                ]
            ],
            'limit' => self::LIMIT,
        ],
        'Global' => [],
        'Country' => [
            'country_code' => [
                'name' => 'Country',
                'type' => 'list',
                'title' => 'Choose your preferred country',
                'values' => [
                    'Argentina' => 21,
                    'Australia' => 23,
                    'Belgium' => 26,
                    'Brazil' => 29,
                    'Canada' => 33,
                    'Colombia' => 36,
                    'Czech Republic' => 307,
                    'France' => 45,
                    'Germany' => 39,
                    'Greece' => 327,
                    'Hong Kong' => 331,
                    'Hungary' => 334,
                    'Iceland' => 265,
                    'India' => 337,
                    'Israel' => 336,
                    'Italy' => 269,
                    'Japan' => 267,
                    'Lithuania' => 357,
                    'Malaysia' => 378,
                    'Mexico' => 65,
                    'Netherlands' => 67,
                    'Philippines' => 390,
                    'Poland' => 392,
                    'Portugal' => 268,
                    'Romania' => 400,
                    'Russia' => 402,
                    'Singapore' => 408,
                    'Slovakia' => 412,
                    'South Africa' => 447,
                    'South Korea' => 348,
                    'Spain' => 270,
                    'Sweden' => 73,
                    'Switzerland' => 34,
                    'Thailand' => 425,
                    'Turkey' => 432,
                    'Ukraine' => 436,
                    'United Kingdom' => 46,
                    'United States' => 78
                ]
            ]
        ]
    ];

    public function getName()
    {
        $feedName = '';
        if ($this->queriedContext == 'Global') {
            $feedName .= 'Netflix Global - ';
        } elseif ($this->queriedContext == 'Country') {
            $feedName .= 'Netflix ' . $this->getParametersKey('country_code') . ' - ';
        }
        if ($this->getInput('feed') == 'expiring') {
            $feedName .= 'Expiring title';
        } elseif ($this->getInput('feed') == 'new last 7 days') {
            $feedName .= 'What\'s New';
        } else {
            $feedName = self::NAME;
        }
        return $feedName;
    }

    private function getParametersKey($input = '')
    {
        $params = $this->getParameters();
        $tab = 'Country';
        if (!isset($params[$tab][$input])) {
            return '';
        }

        return array_search(
            $this->getInput($input),
            $params[$tab][$input]['values']
        );
    }

    private function getJSON($url)
    {
        $header = [
            'Referer: https://unogs.com/',
            'referrer: http://unogs.com'
        ];

        $raw = getContents($url, $header);
        return json_decode($raw, true);
    }

    private function getImage($nfid)
    {
        $url = self::URI . '/api/title/bgimages?netflixid=' . $nfid;
        $json = $this->getJSON($url);
        $image_wrapper = '';
        if (isset($json['bo1280x448'])) {
            $image_wrapper = 'bo1280x448';
        } else {
            $image_wrapper = 'bo665x375';
        }
        end($json[$image_wrapper]);
        $position = key($json[$image_wrapper]);
        $image_link = $json[$image_wrapper][$position]['url'];
        return $image_link;
    }

    private function handleData($data)
    {
        $item = [];
        $item['title'] = $data['title'] . ' - ' . $data['year'];
        $item['timestamp'] = $data['titledate'];
        $netflix_id = $data['nfid'];
        $item['uri'] = 'https://www.netflix.com/title/' . $netflix_id;
        $image_url = $this->getImage($netflix_id);
        $netflix_synopsis = $data['synopsis'];
        $expired_warning = '';
        if (isset($data['expires'])) {
            $expired_warning .= '<p><b>Expired on: ' . $data['expires'] . '</b></p>';
            $item['timestamp'] = $data['expires'];
        }
        $unogs_url = self::URI . '/title/' . $netflix_id;

        $item['content'] = <<<EOD
<img src={$image_url}>
$expired_warning
<p>$netflix_synopsis</p>
<p>Details: <a href={$unogs_url}>$unogs_url</a></p>
EOD;
        $this->items[] = $item;
    }

    public function collectData()
    {
        $feed = $this->getInput('feed');
        $is_global = false;
        $country_code = '';

        switch ($this->queriedContext) {
            case 'Country':
                $country_code = $this->getInput('country_code');
                break;
        }

        $limit = $this->getInput('limit') ?? 30;

        // https://rapidapi.com/unogs/api/unogsng/details
        $api_url = sprintf(
            '%s/api/search?query=%s%s&limit=%s',
            self::URI,
            urlencode($feed),
            $country_code ? '&countrylist=' . $country_code : '',
            $limit
        );

        $json_data = $this->getJSON($api_url);
        $movies = $json_data['results'];

        if ($this->getInput('feed') == 'expiring') {
            /*  uNoGS API returns movies/series that going to remove
            *   today according to the day you fetch the data.
            *   They put items that going to remove in the future on the last
            *   so I reverse this to get those items, not to bothers those that already removed today.
            */
            $movies = array_reverse($movies);
        }

        foreach ($movies as $movie) {
            $this->handleData($movie);
        }
    }
}
