<?php

class SpotifyBridge extends BridgeAbstract
{
    const NAME = 'Spotify';
    const URI = 'https://spotify.com/';
    const DESCRIPTION = 'Fetches the latest albums from one or more artists or the latest tracks from one or more playlists';
    const MAINTAINER = 'Paroleen';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        'global' => [
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
                'name' => 'Country',
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
            ]
        ],
        'artist' => [
            'spotifyuri' => [
                'name' => 'Spotify URIs',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'spotify:artist:4lianjyuR1tqf6oUX8kjrZ [,spotify:artist:3JsMj0DEzyWc0VDlHuy9Bx]',
            ],
            'albumtype' => [
                'name' => 'Album type',
                'type' => 'text',
                'required' => false,
                'exampleValue' => 'album,single,appears_on,compilation',
                'defaultValue' => 'album,single'
            ]
        ],
        'playlist' => [
            'spotifyuri' => [
                'name' => 'Spotify URIs',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'spotify:playlist:37i9dQZF1DXcBWIGoYBM5M [,spotify:playlist:37i9dQZF1DX0XUsuxWHRQd]',
            ],
        ]
    ];

    const TOKENURI = 'https://accounts.spotify.com/api/token';
    const APIURI = 'https://api.spotify.com/v1/';

    private $uri = '';
    private $name = '';
    private $token = '';
    private $artists = [];
    private $albums = [];
    private $playlists = [];
    private $tracks = [];

    public function getURI()
    {
        if (empty($this->uri)) {
            $this->getArtist();
        }

        return $this->uri;
    }

    public function getName()
    {
        if (empty($this->name)) {
            if ($this->queriedContext == 'artist') {
                $this->getArtist();
            } else {
                $this->getPlaylist();
            }
        }

        return $this->name;
    }

    public function getIcon()
    {
        return 'https://www.scdn.co/i/_global/favicon.png';
    }

    private function getId($item)
    {
        return explode(':', $item)[2];
    }

    private function getDate($date)
    {
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

    private function getAlbumType()
    {
        return $this->getInput('albumtype');
    }

    private function getCountry()
    {
        return $this->getInput('country');
    }

    private function getToken()
    {
        $cacheFactory = new CacheFactory();

        $cache = $cacheFactory->create();
        $cache->setScope('SpotifyBridge');
        $cache->setKey(['token']);

        if ($cache->getTime()) {
            $time = (new DateTime())->getTimestamp() - $cache->getTime();
            Debug::log('Token time: ' . $time);
        }

        if ($cache->getTime() == false || $time >= 3600) {
            Debug::log('Fetching token from Spotify');
            $this->fetchToken();
            $cache->saveData($this->token);
        } else {
            Debug::log('Loading token from cache');
            $this->token = $cache->loadData();
        }

        Debug::log('Token: ' . $this->token);
    }

    private function getArtist()
    {
        if (!is_null($this->getInput('spotifyuri')) && strpos($this->getInput('spotifyuri'), ',') === false) {
            $artist = $this->fetchContent(self::APIURI . 'artists/'
                . $this->getId($this->artists[0]));
            $this->uri = $artist['external_urls']['spotify'];
            $this->name = $artist['name'] . ' - Spotify';
        } else {
            $this->uri = parent::getURI();
            $this->name = parent::getName();
        }
    }

    private function getAllArtists()
    {
        Debug::log('Parsing all artists');
        $this->artists = explode(',', $this->getInput('spotifyuri'));
    }

    private function getAllAlbums()
    {
        $this->albums = [];

        $this->getAllArtists();

        Debug::log('Fetching all albums');
        foreach ($this->artists as $artist) {
            $fetch = true;
            $offset = 0;

            while ($fetch) {
                $partial_albums = $this->fetchContent(self::APIURI . 'artists/'
                    . $this->getId($artist)
                    . '/albums?limit=50&include_groups='
                    . $this->getAlbumType()
                    . '&country='
                    . $this->getCountry()
                    . '&offset='
                    . $offset);

                if (!empty($partial_albums['items'])) {
                    $this->albums = array_merge(
                        $this->albums,
                        $partial_albums['items']
                    );
                } else {
                    $fetch = false;
                }

                $offset += 50;
            }
        }
    }

    private function getPlaylist()
    {
        if (!is_null($this->getInput('spotifyuri')) && strpos($this->getInput('spotifyuri'), ',') === false) {
            $playlist = $this->fetchContent(self::APIURI . 'playlists/'
                . $this->getId($this->playlists[0]));
            $this->uri = $playlist['external_urls']['spotify'];
            $this->name = $playlist['name'] . ' - Spotify';
        } else {
            $this->uri = parent::getURI();
            $this->name = parent::getName();
        }
    }

    private function getAllPlaylists()
    {
        Debug::log('Parsing all playlists');
        $this->playlists = explode(',', $this->getInput('spotifyuri'));
    }

    private function getAllTracks()
    {
        $this->tracks = [];

        $this->getAllPlaylists();

        Debug::log('Fetching all tracks');
        foreach ($this->playlists as $playlist) {
            $fetch = true;
            $offset = 0;

            while ($fetch) {
                $partial_tracks = $this->fetchContent(self::APIURI . 'playlists/'
                    . $this->getId($playlist)
                    . '/tracks?limit=50&country='
                    . $this->getCountry()
                    . '&offset='
                    . $offset);

                if (!empty($partial_tracks['items'])) {
                    $this->tracks = array_merge(
                        $this->tracks,
                        $partial_tracks['items']
                    );
                } else {
                    $fetch = false;
                }

                $offset += 50;
            }
        }
    }

    private function fetchToken()
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, self::TOKENURI);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic '
            . base64_encode($this->getInput('clientid')
            . ':'
            . $this->getInput('clientsecret'))]);

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
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer '
            . $this->token]);

        Debug::log('Fetching content from ' . $url);
        $json = curl_exec($curl);
        $json = json_decode($json, true);
        curl_close($curl);

        return $json;
    }

    private function sortAlbums()
    {
        Debug::log('Sorting albums');
        usort($this->albums, function ($album1, $album2) {
            if ($this->getDate($album1['release_date']) < $this->getDate($album2['release_date'])) {
                return 1;
            } else {
                return -1;
            }
        });
    }

    private function sortTracks()
    {
        Debug::log('Sorting tracks');
        usort($this->tracks, function ($track1, $track2) {
            if ($this->getDate($track1['added_at']) < $this->getDate($track2['added_at'])) {
                return 1;
            } else {
                return -1;
            }
        });
    }

    private function collectAlbums()
    {
        $offset = 0;

        $this->getAllAlbums();
        $this->sortAlbums();

        Debug::log('Building RSS feed');
        foreach ($this->albums as $album) {
            $item = [];
            $item['title'] = $album['name'];
            $item['uri'] = $album['external_urls']['spotify'];

            $item['timestamp'] = $this->getDate($album['release_date']);
            $item['author'] = $album['artists'][0]['name'];
            $item['categories'] = [$album['album_type']];

            $item['content'] = '<img style="width: 256px" src="'
                . $album['images'][0]['url']
                . '">';

            if ($album['total_tracks'] > 1) {
                $item['content'] .= '<p>Total tracks: '
                    . $album['total_tracks']
                    . '</p>';
            }

            $this->items[] = $item;

            if ($this->getInput('limit') > 0 && count($this->items) >= $this->getInput('limit')) {
                break;
            }
        }
    }

    private function collectTracks()
    {
        $offset = 0;

        $this->getAllTracks();
        $this->sortTracks();

        Debug::log('Building RSS feed');
        foreach ($this->tracks as $track) {
            $item = [];

            $item['title'] = $track['track']['name'];
            $item['uri'] = $track['track']['external_urls']['spotify'];

            $item['timestamp'] = $this->getDate($track['added_at']);
            $item['author'] = $track['artists'][0]['name'];
            $item['categories'] = ['track'];

            $item['content'] = '<img style="width: 256px" src="'
                . $track['track']['album']['images'][0]['url']
                . '">';

            $this->items[] = $item;

            if ($this->getInput('limit') > 0 && count($this->items) >= $this->getInput('limit')) {
                break;
            }
        }
    }

    public function collectData()
    {
        if ($this->queriedContext == 'artist') {
            $this->collectAlbums();
        } else {
            $this->collectTracks();
        }
    }
}
