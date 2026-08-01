<?php

class BandcampBridge extends BridgeAbstract
{
    const MAINTAINER = 'sebsauvage, Roliga';
    const NAME = 'Bandcamp';
    const URI = 'https://bandcamp.com/';
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = 'New bandcamp releases by tag, band or album';
    const PARAMETERS = [
        'By tag' => [
            'tag' => [
                'name' => 'tag',
                'type' => 'text',
                'required' => true,
                'exampleValue'  => 'hip-hop-rap'
            ]
        ],
        'By band' => [
            'band' => [
                'name' => 'band',
                'type' => 'text',
                'title' => 'Band name as seen in the band page URL',
                'required' => true,
                'exampleValue'  => 'aesoprock'
            ],
            'type' => [
                'name' => 'Articles are',
                'type' => 'list',
                'values' => [
                    'Releases' => 'releases',
                    'Releases, new one when track list changes' => 'changes',
                    'Individual tracks' => 'tracks'
                ],
                'defaultValue' => 'changes'
            ],
            'limit' => [
                'name' => 'limit',
                'type' => 'number',
                'required' => true,
                'title' => 'Number of releases to return',
                'defaultValue' => 5
            ]
        ],
        'By label' => [
            'label' => [
                'name' => 'label',
                'type' => 'text',
                'title' => 'label name as seen in the label page URL',
                'required' => true
            ],
            'type' => [
                'name' => 'Articles are',
                'type' => 'list',
                'values' => [
                    'Releases' => 'releases',
                    'Releases, new one when track list changes' => 'changes',
                    'Individual tracks' => 'tracks'
                ],
                'defaultValue' => 'changes'
            ],
            'limit' => [
                'name' => 'limit',
                'type' => 'number',
                'title' => 'Number of releases to return',
                'defaultValue' => 5
            ]
        ],
        'By album' => [
            'band' => [
                'name' => 'band',
                'type' => 'text',
                'title' => 'Band name as seen in the album page URL',
                'required' => true,
                'exampleValue'  => 'aesoprock'
            ],
            'album' => [
                'name' => 'album',
                'type' => 'text',
                'title' => 'Album name as seen in the album page URL',
                'required' => true,
                'exampleValue'  => 'appleseed'
            ],
            'type' => [
                'name' => 'Articles are',
                'type' => 'list',
                'values' => [
                    'Releases' => 'releases',
                    'Releases, new one when track list changes' => 'changes',
                    'Individual tracks' => 'tracks'
                ],
                'defaultValue' => 'tracks'
            ]
        ]
    ];
    const IMGURI = 'https://f4.bcbits.com/';
    const IMGSIZE_300PX = 23;
    const IMGSIZE_700PX = 16;

    private $feedName;

    public function getIcon()
    {
        return 'https://s4.bcbits.com/img/bc_favicon.ico';
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'By tag':
                $url = self::URI . 'api/discover/1/discover_web';
                $data = $this->buildTagRequestJson();
                $header = [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data),
                ];
                $opts = [
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data,
                ];
                $content = getContents($url, $header, $opts);

                $json = json_decode($content);

                if (!isset($json->results) || !is_array($json->results)) {
                    $this->logger->info('Invalid bandcamp response', ['response' => mb_substr($content, 0, 500)]);
                    throwServerException('Invalid response');
                }

                foreach ($json->results as $entry) {
                    $this->items[] = $this->buildTagItem($entry);
                }
                break;
            case 'By band':
            case 'By label':
            case 'By album':
                $html = getSimpleHTMLDOMCached($this->getURI(), 86400);

                if ($html->find('meta[name=title]', 0)) {
                    $this->feedName = $html->find('meta[name=title]', 0)->content;
                } else {
                    $this->feedName = str_replace('Music | ', '', $html->find('title', 0)->plaintext);
                }

                $regex = '/band_id=(\d+)/';
                if (preg_match($regex, $html, $matches) == false) {
                    throwClientException('Unable to find band ID on: ' . $this->getURI());
                }
                $band_id = $matches[1];

                $tralbums = [];
                switch ($this->queriedContext) {
                    case 'By band':
                    case 'By label':
                        $query_data = [
                        'band_id' => $band_id
                        ];
                        $band_data = $this->apiGet('mobile/22/band_details', $query_data);

                        $num_albums = min(count($band_data->discography), $this->getInput('limit'));
                        for ($i = 0; $i < $num_albums; $i++) {
                            $album_basic_data = $band_data->discography[$i];

                            // 'a' or 't' for albums and individual tracks respectively
                            $tralbum_type = substr($album_basic_data->item_type, 0, 1);

                            $query_data = [
                            'band_id' => $band_id,
                            'tralbum_type' => $tralbum_type,
                            'tralbum_id' => $album_basic_data->item_id
                            ];
                            $tralbums[] = $this->apiGet('mobile/22/tralbum_details', $query_data);
                        }
                        break;
                    case 'By album':
                        $regex = '/album=(\d+)/';
                        if (preg_match($regex, $html, $matches) == false) {
                            throwClientException('Unable to find album ID on: ' . $this->getURI());
                        }
                        $album_id = $matches[1];

                        $query_data = [
                        'band_id' => $band_id,
                        'tralbum_type' => 'a',
                        'tralbum_id' => $album_id
                        ];
                        $tralbums[] = $this->apiGet('mobile/22/tralbum_details', $query_data);

                        break;
                }

                foreach ($tralbums as $tralbum_data) {
                    if ($tralbum_data->type === 'a' && $this->getInput('type') === 'tracks') {
                        foreach ($tralbum_data->tracks as $track) {
                            $query_data = [
                            'band_id' => $band_id,
                            'tralbum_type' => 't',
                            'tralbum_id' => $track->track_id
                            ];
                            $track_data = $this->apiGet('mobile/22/tralbum_details', $query_data);

                            $this->items[] = $this->buildTralbumItem($track_data);
                        }
                    } else {
                        $this->items[] = $this->buildTralbumItem($tralbum_data);
                    }
                }
                break;
        }
    }

    /*
     * Builds a feed item from one entry of the discover_web API's "results" array.
     * Each $entry is a release object with the fields:
     *   item_url       string   public URL of the album/track
     *   title          string   release title
     *   band_name      string   name the release is published under (band or label)
     *   album_artist   ?string  artist name; null when it equals the band itself
     *   primary_image  ?object  cover art; ->image_id (int) builds the art URL
     *   result_type    string   'a' = album, 't' = track
     */
    private function buildTagItem($entry)
    {
        $url = $entry->item_url;
        // album_artist is null when the release artist is the band itself
        $artist = $entry->album_artist ?? $entry->band_name;
        $title = $entry->title;
        // e.g. record label is the releaser, but not the artist
        $releaser = $entry->band_name !== $artist ? $entry->band_name : null;

        $full_title = $artist . ' - ' . $title;
        $full_artist = $artist;
        if (isset($releaser)) {
            $full_title .= ' (' . $releaser . ')';
            $full_artist .= ' (' . $releaser . ')';
        }
        $art_id = $entry->primary_image->image_id ?? null;
        $small_img = $this->getImageUrl($art_id, self::IMGSIZE_300PX);
        $img = $this->getImageUrl($art_id, self::IMGSIZE_700PX);

        return [
            'uri' => $url,
            'author' => $full_artist,
            'title' => $full_title,
            'content' => "<img src='$small_img' /><br/>$full_title",
            'enclosures' => [$img],
        ];
    }

    private function buildTralbumItem($tralbum_data)
    {
        $band_data = $tralbum_data->band;

        // Format title like: ARTIST - ALBUM/TRACK (OPTIONAL RELEASER)
        // Format artist/author like: ARTIST (OPTIONAL RELEASER)
        //
        // If the album/track is released under a label/a band other than the artist
        // themselves, append that releaser name to the title and artist/author.
        //
        // This sadly doesn't always work right for individual tracks as the artist
        // of the track is always set to the releaser.
        $artist = $tralbum_data->tralbum_artist;
        $full_title = $artist . ' - ' . $tralbum_data->title;
        $full_artist = $artist;
        if (isset($tralbum_data->label)) {
            $full_title .= ' (' . $tralbum_data->label . ')';
            $full_artist .= ' (' . $tralbum_data->label . ')';
        } elseif ($band_data->name !== $artist) {
            $full_title .= ' (' . $band_data->name . ')';
            $full_artist .= ' (' . $band_data->name . ')';
        }

        $small_img = $this->getImageUrl($tralbum_data->art_id, self::IMGSIZE_300PX);
        $img = $this->getImageUrl($tralbum_data->art_id, self::IMGSIZE_700PX);

        $item = [
            'uri' => $tralbum_data->bandcamp_url,
            'author' => $full_artist,
            'title' => $full_title,
            'enclosures' => [$img],
            'timestamp' => $tralbum_data->release_date
        ];

        $item['categories'] = [];
        foreach ($tralbum_data->tags as $tag) {
            $item['categories'][] = $tag->norm_name;
        }

        // Give articles a unique UID depending on its track list
        // Releases should then show up as new articles when tracks are added
        if ($this->getInput('type') === 'changes') {
            $item['uid'] = "bandcamp/$band_data->band_id/$tralbum_data->id/";
            foreach ($tralbum_data->tracks as $track) {
                $item['uid'] .= $track->track_id;
            }
        }

        $item['content'] = "<img src='$small_img' /><br/>$full_title<br/>";
        if ($tralbum_data->type === 'a') {
            $item['content'] .= '<ol>';
            foreach ($tralbum_data->tracks as $track) {
                $item['content'] .= "<li>$track->title</li>";
            }
            $item['content'] .= '</ol>';
        }
        if (!empty($tralbum_data->about)) {
            $item['content'] .= '<p>'
                . nl2br($tralbum_data->about)
                . '</p>';
        }

        return $item;
    }

    private function buildTagRequestJson()
    {
        $requestJson = [
            'tag_norm_names' => [$this->getInput('tag')],
            'category_id' => 0,
            'geoname_id' => 0,
            'slice' => 'new',
            'time_facet_id' => null,
            'cursor' => '*',
            'size' => 60,
            'include_result_types' => ['a', 's'],
        ];
        return json_encode($requestJson);
    }

    private function getImageUrl($id, $size)
    {
        return self::IMGURI . 'img/a' . $id . '_' . $size . '.jpg';
    }

    private function apiGet($endpoint, $query_data)
    {
        $url = self::URI . 'api/' . $endpoint . '?' . http_build_query($query_data);
        // todo: 429 Too Many Requests happens a lot
        $response = getContents($url);
        $data = json_decode($response);
        return $data;
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'By tag':
                if (!is_null($this->getInput('tag'))) {
                    return self::URI
                    . 'tag/'
                    . urlencode($this->getInput('tag'))
                    . '?sort_field=date';
                }
                break;
            case 'By label':
                if (!is_null($this->getInput('label'))) {
                    return 'https://'
                    . $this->getInput('label')
                    . '.bandcamp.com/music';
                }
                break;
            case 'By band':
                if (!is_null($this->getInput('band'))) {
                    return 'https://'
                    . $this->getInput('band')
                    . '.bandcamp.com/music';
                }
                break;
            case 'By album':
                if (!is_null($this->getInput('band')) && !is_null($this->getInput('album'))) {
                    return 'https://'
                    . $this->getInput('band')
                    . '.bandcamp.com/album/'
                    . $this->getInput('album');
                }
                break;
        }

        return parent::getURI();
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By tag':
                if (!is_null($this->getInput('tag'))) {
                    return $this->getInput('tag') . ' - Bandcamp Tag';
                }
                break;
            case 'By band':
                if (isset($this->feedName)) {
                    return $this->feedName . ' - Bandcamp Band';
                } elseif (!is_null($this->getInput('band'))) {
                    return $this->getInput('band') . ' - Bandcamp Band';
                }
                break;
            case 'By label':
                if (isset($this->feedName)) {
                    return $this->feedName . ' - Bandcamp Label';
                } elseif (!is_null($this->getInput('label'))) {
                    return $this->getInput('label') . ' - Bandcamp Label';
                }
                break;
            case 'By album':
                if (isset($this->feedName)) {
                    return $this->feedName . ' - Bandcamp Album';
                } elseif (!is_null($this->getInput('album'))) {
                    return $this->getInput('album') . ' - Bandcamp Album';
                }
                break;
        }

        return parent::getName();
    }

    public function detectParameters($url)
    {
        $params = [];

        // By tag
        $regex = '/^(https?:\/\/)?bandcamp\.com\/tag\/([^\/.&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By tag';
            $params['tag'] = urldecode($matches[2]);
            return $params;
        }

        // By band
        $regex = '/^(https?:\/\/)?([^\/.&?\n]+?)\.bandcamp\.com/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By band';
            $params['band'] = urldecode($matches[2]);
            return $params;
        }

        // By album
        $regex = '/^(https?:\/\/)?([^\/.&?\n]+?)\.bandcamp\.com\/album\/([^\/.&?\n]+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'By album';
            $params['band'] = urldecode($matches[2]);
            $params['album'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }
}
