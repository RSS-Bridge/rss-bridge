<?php

class SpotifyBridge extends BridgeAbstract
{
    const NAME = 'Spotify';
    const URI = 'https://spotify.com/';
    const DESCRIPTION = 'Fetches the latest items from one or more artists, playlists or podcasts';
    const MAINTAINER = 'Paroleen';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        'By Spotify URIs' => [
            'clientid' => [
                'name' => 'Client ID',
                'type' => 'text',
                'required' => true
            ],
            'clientsecret' => [
                'name' => 'Client secret',
                'type' => 'text',
                'required' => true
            ],
            'country' => [
                'name' => 'Country/Market',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'US',
                'defaultValue' => 'US'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'exampleValue' => 10,
                'defaultValue' => 10
            ],
            'spotifyuri' => [
                'name' => 'Spotify URIs',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'spotify:artist:4lianjyuR1tqf6oUX8kjrZ [,spotify:playlist:37i9dQZF1DXcBWIGoYBM5M,spotify:show:6ShFMYxeDNMo15COLObDvC]',
            ],
            'albumtype' => [
                'name' => 'Album type',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'album,single,appears_on,compilation',
                'defaultValue' => 'album,single'
            ]
        ],
        'By Spotify Search' => [
            'clientid' => [
                'name' => 'Client ID',
                'type' => 'text',
                'required' => true
            ],
            'clientsecret' => [
                'name' => 'Client secret',
                'type' => 'text',
                'required' => true
            ],
            'market' => [
                'name' => 'Market',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'US',
                'defaultValue' => 'US'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'exampleValue' => 10,
                'defaultValue' => 10
            ],
            'query' => [
                'name' => 'Search query',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'artist:The Beatles',
            ],
            'type' => [
                'name' => 'Type',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'album,episode',
                'defaultValue' => 'album,episode'
            ]
        ],
    ];

    private $uri = '';
    private $name = '';
    private $token = '';

    public function collectData()
    {
        $this->fetchAccessToken();

        if ($this->queriedContext === 'By Spotify URIs') {
            $entries = $this->getEntriesFromURIs();
        } else {
            $entries = $this->getEntriesFromQuery();
        }

        usort($entries, function ($entry1, $entry2) {
            return $this->getDate($entry2) <=> $this->getDate($entry1);
        });

        foreach ($entries as $entry) {
            if (! isset($entry['type'])) {
                $item = $this->getTrackData($entry);
            } elseif ($entry['type'] === 'album') {
                $item = $this->getAlbumData($entry);
            } elseif ($entry['type'] === 'episode') {
                $item = $this->getEpisodeData($entry);
            } else {
                throw new \Exception('Spotify URI not supported');
            }

            $this->items[] = $item;

            if ($this->getInput('limit') > 0 && count($this->items) >= $this->getInput('limit')) {
                break;
            }
        }
    }

    private function getEntriesFromQuery()
    {
        $entries = [];

        $types = [
            'albums',
            'episodes',
        ];

        $query = [
            'q' => $this->getInput('query'),
            'type' => $this->getInput('type'),
            'market' => $this->getInput('market'),
            'limit' => 50,
        ];

        $hasItems = true;
        $offset = 0;

        while ($hasItems && $offset < 1000) {
            $hasItems = false;

            $query['offset'] = $offset;
            $json = getContents('https://api.spotify.com/v1/search?' . http_build_query($query), ['Authorization: Bearer ' . $this->token]);
            $partial = Json::decode($json);

            foreach ($types as $type) {
                if (isset($partial[$type]['items'])) {
                    $entries = array_merge($entries, $partial[$type]['items']);
                    $hasItems = true;
                }
            }

            $offset += 50;
        }

        return $entries;
    }

    private function getEntriesFromURIs()
    {
        $entries = [];
        $uris = explode(',', $this->getInput('spotifyuri'));

        foreach ($uris as $uri) {
            $type = explode(':', $uri)[1];
            $spotifyId = explode(':', $uri)[2];

            $types = [
                'artist' => 'album',
                'playlist' => 'track',
                'show' => 'episode',
            ];
            if (!isset($types[$type])) {
                throw new \Exception(sprintf('Unsupported Spotify URI: %s', $uri));
            }
            $entry_type = $types[$type];

            $url = 'https://api.spotify.com/v1/' . $type . 's/' . $spotifyId . '/' . $entry_type . 's';
            $query = [
                'limit' => 50,
            ];

            if ($type === 'artist') {
                $query['country'] = $this->getInput('country');
                $query['include_groups'] = $this->getInput('albumtype');
            } else {
                $query['market'] = $this->getInput('country');
            }

            $offset = 0;
            while (true) {
                $query['offset'] = $offset;
                $json = getContents($url . '?' . http_build_query($query), ['Authorization: Bearer ' . $this->token]);
                $partial = Json::decode($json);
                if (empty($partial['items'])) {
                    break;
                }
                $entries = array_merge($entries, $partial['items']);
                $offset += 50;
            }
        }
        return $entries;
    }

    private function getAlbumData($album)
    {
        $item = [];
        $item['title'] = $album['name'];
        $item['uri'] = $album['external_urls']['spotify'];
        $item['timestamp'] = $this->getDate($album);
        $item['author'] = $album['artists'][0]['name'];
        $item['categories'] = [$album['album_type']];
        $item['content'] = '<img style="width: 256px" src="' . $album['images'][0]['url'] . '">';
        if ($album['total_tracks'] > 1) {
            $item['content'] .= '<p>Total tracks: ' . $album['total_tracks'] . '</p>';
        }
        return $item;
    }

    private function getTrackData($track)
    {
        $item = [];
        $item['title'] = $track['track']['name'];
        $item['uri'] = $track['track']['external_urls']['spotify'];
        $item['timestamp'] = $this->getDate($track);
        $item['author'] = $track['track']['artists'][0]['name'];
        $item['categories'] = ['track'];
        $item['content'] = '<img style="width: 256px" src="' . $track['track']['album']['images'][0]['url'] . '">';
        return $item;
    }

    private function getEpisodeData($episode)
    {
        $item = [];
        $item['title'] = $episode['name'];
        $item['uri'] = $episode['external_urls']['spotify'];
        $item['timestamp'] = $this->getDate($episode);
        $item['content'] = '<img style="width: 256px" src="' . $episode['images'][0]['url'] . '">';
        if (isset($episode['description'])) {
            $item['content'] = $item['content'] . '<p>' . $episode['description'] . '</p>';
        }
        if (isset($episode['audio_preview_url'])) {
            $item['content'] = $item['content'] . '<audio controls src="' . $episode['audio_preview_url'] . '"></audio>';
        }
        return $item;
    }

    private function getDate($entry)
    {
        if (isset($entry['type'])) {
            $type = 'release_date';
        } else {
            $type = 'added_at';
        }

        $date = $entry[$type];

        if (strlen($date) == 4) {
            $date .= '-01-01';
        } elseif (strlen($date) == 7) {
            $date .= '-01';
        }

        if (strlen($date) > 10) {
            return DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date)->getTimestamp();
        }

        return DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
    }

    private function fetchAccessToken()
    {
        $cacheKey = sprintf('SpotifyBridge:%s:%s', $this->getInput('clientid'), $this->getInput('clientsecret'));

        $token = $this->cache->get($cacheKey);
        if ($token) {
            $this->token = $token;
        } else {
            $basicAuth = base64_encode(sprintf('%s:%s', $this->getInput('clientid'), $this->getInput('clientsecret')));
            $json = getContents('https://accounts.spotify.com/api/token', [
                "Authorization: Basic $basicAuth"
            ], [
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
            ]);
            $data = Json::decode($json);
            $this->token = $data['access_token'];

            $this->cache->set($cacheKey, $this->token, 3600);
        }
    }

    public function getURI()
    {
        if (empty($this->uri)) {
            $this->getFirstEntry();
        }

        return $this->uri;
    }

    public function getName()
    {
        if (empty($this->name)) {
            $this->getFirstEntry();
        }

        return $this->name;
    }

    private function getFirstEntry()
    {
        $uris = explode(',', $this->getInput('spotifyuri'));
        if (!is_null($this->getInput('spotifyuri')) && strpos($this->getInput('spotifyuri'), ',') === false) {
            $firstUri = $uris[0];
            $type = explode(':', $firstUri)[1];
            $spotifyId = explode(':', $firstUri)[2];

            $uri = 'https://api.spotify.com/v1/' . $type . 's/' . $spotifyId;
            $query = [];
            if ($type === 'show') {
                $query['market'] = $this->getInput('country');
            }

            $json = getContents($uri . '?' . http_build_query($query), ['Authorization: Bearer ' . $this->token]);
            $item = Json::decode($json);

            $this->uri = $item['external_urls']['spotify'];
            $this->name = $item['name'] . ' - Spotify';
        } else {
            $this->uri = parent::getURI();
            $this->name = parent::getName();
        }
    }

    public function getIcon()
    {
        return 'https://www.scdn.co/i/_global/favicon.png';
    }
}
