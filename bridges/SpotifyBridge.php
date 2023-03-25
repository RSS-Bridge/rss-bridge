<?php

class SpotifyBridge extends BridgeAbstract
{
    const NAME = 'Spotify';
    const URI = 'https://spotify.com/';
    const DESCRIPTION = 'Fetches the latest items from one or more artists, playlists or podcasts';
    const MAINTAINER = 'Paroleen';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [ [
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
    ] ];

    private $uri = '';
    private $name = '';
    private $token = '';

    public function collectData()
    {
        $entries = $this->getAllEntries();
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

    private function getAllEntries()
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
                throw new \Exception('Spotify URI not supported');
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
                $partial = $this->fetchContent($url . '?' . http_build_query($query));
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

    private function getToken()
    {
        $cacheFactory = new CacheFactory();

        $cache = $cacheFactory->create();
        $cache->setScope('SpotifyBridge');

        $cacheKey = sprintf('%s:%s', $this->getInput('clientid'), $this->getInput('clientsecret'));
        $cache->setKey($cacheKey);

        $time = null;
        if ($cache->getTime()) {
            $time = (new DateTime())->getTimestamp() - $cache->getTime();
        }

        if ($cache->getTime() == false || $time >= 3600) {
            $this->fetchToken();
            $cache->saveData($this->token);
        } else {
            $this->token = $cache->loadData();
        }
    }

    private function fetchToken()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');

        $basic = sprintf('%s:%s', $this->getInput('clientid'), $this->getInput('clientsecret'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($basic)]);

        $json = curl_exec($curl);
        $json = json_decode($json)->access_token;
        curl_close($curl);

        $this->token = $json;
    }

    private function fetchContent($url)
    {
        $this->getToken();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->token]);
        $json = curl_exec($curl);
        $json = json_decode($json, true);
        curl_close($curl);
        return $json;
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

            $item = $this->fetchContent($uri . '?' . http_build_query($query));

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
