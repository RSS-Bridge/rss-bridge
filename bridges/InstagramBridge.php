<?php

class InstagramBridge extends BridgeAbstract
{
    // const MAINTAINER = 'pauder';
    const NAME = 'Instagram Bridge';
    const URI = 'https://www.instagram.com/';
    const DESCRIPTION = 'Returns the newest images';

    const CONFIGURATION = [
        'session_id' => [
            'required' => false,
        ],
        'cache_timeout' => [
            'required' => false,
        ],
        'ds_user_id' => [
            'required' => false,
        ],
    ];

    const PARAMETERS = [
        'Username' => [
            'u' => [
                'name' => 'username',
                'exampleValue' => 'aesoprockwins',
                'required' => true
            ]
        ],
        'Hashtag' => [
            'h' => [
                'name' => 'hashtag',
                'exampleValue' => 'beautifulday',
                'required' => true
            ]
        ],
        'Location' => [
            'l' => [
                'name' => 'location',
                'exampleValue' => 'london',
                'required' => true
            ]
        ],
        'global' => [
            'media_type' => [
                'name' => 'Media type',
                'type' => 'list',
                'required' => false,
                'values' => [
                    'All' => 'all',
                    'Video' => 'video',
                    'Picture' => 'picture',
                    'Multiple' => 'multiple',
                ],
                'defaultValue' => 'all'
            ],
            'direct_links' => [
                'name' => 'Use direct media links',
                'type' => 'checkbox',
            ]
        ]

    ];

    const TEST_DETECT_PARAMETERS = [
        'https://www.instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
        'https://instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
        'http://www.instagram.com/metaverse' => ['context' => 'Username', 'u' => 'metaverse'],
    ];

    const USER_QUERY_HASH = '58b6785bea111c67129decbe6a448951';
    const TAG_QUERY_HASH = '9b498c08113f1e09617a1703c22b2f32';
    const SHORTCODE_QUERY_HASH = '865589822932d1b43dfe312121dd353a';

    public function getCacheTimeout()
    {
        $customTimeout = $this->getOption('cache_timeout');
        if ($customTimeout) {
            return $customTimeout;
        }
        return parent::getCacheTimeout();
    }

    protected function getContents($uri)
    {
        $headers = [];
        $sessionId = $this->getOption('session_id');
        $dsUserId = $this->getOption('ds_user_id');
        if ($sessionId and $dsUserId) {
            $headers[] = 'cookie: sessionid=' . $sessionId . '; ds_user_id=' . $dsUserId;
        }
        return getContents($uri, $headers);
    }

    protected function getInstagramUserId($username)
    {
        if (is_numeric($username)) {
            return $username;
        }

        $cacheKey = 'InstagramBridge_' . $username;
        $pk = $this->cache->get($cacheKey);

        if (!$pk) {
            $data = $this->getContents(self::URI . 'web/search/topsearch/?query=' . $username);
            foreach (json_decode($data)->users as $user) {
                if (strtolower($user->user->username) === strtolower($username)) {
                    $pk = $user->user->pk;
                }
            }
            if (!$pk) {
                returnServerError('Unable to find username in search result.');
            }
            $this->cache->set($cacheKey, $pk);
        }
        return $pk;
    }

    public function collectData()
    {
        $directLink = !is_null($this->getInput('direct_links')) && $this->getInput('direct_links');

        $data = $this->getInstagramJSON($this->getURI());

        if (!is_null($this->getInput('u'))) {
            $userMedia = $data->data->user->edge_owner_to_timeline_media->edges;
        } elseif (!is_null($this->getInput('h'))) {
            $userMedia = $data->data->hashtag->edge_hashtag_to_media->edges;
        } elseif (!is_null($this->getInput('l'))) {
            $userMedia = $data->entry_data->LocationsPage[0]->graphql->location->edge_location_to_media->edges;
        }

        foreach ($userMedia as $media) {
            $media = $media->node;

            switch ($this->getInput('media_type')) {
                case 'all':
                    break;
                case 'video':
                    if ($media->__typename != 'GraphVideo' || !$media->is_video) {
                        continue 2;
                    }
                    break;
                case 'picture':
                    if ($media->__typename != 'GraphImage') {
                        continue 2;
                    }
                    break;
                case 'multiple':
                    if ($media->__typename != 'GraphSidecar') {
                        continue 2;
                    }
                    break;
                default:
                    break;
            }

            $item = [];
            $item['uri'] = self::URI . 'p/' . $media->shortcode . '/';

            if (isset($media->owner->username)) {
                $item['author'] = $media->owner->username;
            }

            $textContent = $this->getTextContent($media);

            $item['title'] = ($media->is_video ? '▶ ' : '') . $textContent;
            $titleLinePos = strpos(wordwrap($item['title'], 120), "\n");
            if ($titleLinePos != false) {
                $item['title'] = substr($item['title'], 0, $titleLinePos) . '...';
            }

            if ($directLink) {
                $mediaURI = $media->display_url;
            } else {
                $mediaURI = self::URI . 'p/' . $media->shortcode . '/media?size=l';
            }

            $pattern = ['/\@([\w\.]+)/', '/#([\w\.]+)/'];
            $replace = [
                '<a href="https://www.instagram.com/$1">@$1</a>',
                '<a href="https://www.instagram.com/explore/tags/$1">#$1</a>'];

            switch ($media->__typename) {
                case 'GraphSidecar':
                    $data = $this->getInstagramSidecarData($item['uri'], $item['title'], $media, $textContent);
                    $item['content'] = $data[0];
                    $item['enclosures'] = $data[1];
                    break;
                case 'GraphImage':
                    $item['content'] = '<a href="' . htmlentities($item['uri']) . '" target="_blank">';
                    $item['content'] .= '<img src="' . htmlentities($mediaURI) . '" alt="' . $item['title'] . '" />';
                    $item['content'] .= '</a><br><br>' . nl2br(preg_replace($pattern, $replace, htmlentities($textContent)));
                    $item['enclosures'] = [$mediaURI];
                    break;
                case 'GraphVideo':
                    $data = $this->getInstagramVideoData($item['uri'], $mediaURI, $media, $textContent);
                    $item['content'] = $data[0];
                    if ($directLink) {
                        $item['enclosures'] = $data[1];
                    } else {
                        $item['enclosures'] = [$mediaURI];
                    }
                    $item['thumbnail'] = $mediaURI;
                    break;
                default:
                    break;
            }
            $item['timestamp'] = $media->taken_at_timestamp;

            $this->items[] = $item;
        }
    }

    // returns Sidecar(a post which has multiple media)'s contents and enclosures
    protected function getInstagramSidecarData($uri, $postTitle, $mediaInfo, $textContent)
    {
        $enclosures = [];
        $content = '';
        foreach ($mediaInfo->edge_sidecar_to_children->edges as $singleMedia) {
            $singleMedia = $singleMedia->node;
            if ($singleMedia->is_video) {
                if (in_array($singleMedia->video_url, $enclosures)) {
                    continue; // check if not added yet
                }
                $content .= '<video controls><source src="' . $singleMedia->video_url . '" type="video/mp4"></video><br>';
                array_push($enclosures, $singleMedia->video_url);
            } else {
                if (in_array($singleMedia->display_url, $enclosures)) {
                    continue; // check if not added yet
                }
                $content .= '<a href="' . $singleMedia->display_url . '" target="_blank">';
                $content .= '<img src="' . $singleMedia->display_url . '" alt="' . $postTitle . '" />';
                $content .= '</a><br>';
                array_push($enclosures, $singleMedia->display_url);
            }
        }
        $content .= '<br>' . nl2br(htmlentities($textContent));

        return [$content, $enclosures];
    }

    // returns Video post's contents and enclosures
    protected function getInstagramVideoData($uri, $mediaURI, $mediaInfo, $textContent)
    {
        $content = '<video controls>';
        $content .= '<source src="' . $mediaInfo->video_url . '" poster="' . $mediaURI . '" type="video/mp4">';
        $content .= '<img src="' . $mediaURI . '" alt="">';
        $content .= '</video><br>';
        $content .= '<br>' . nl2br(htmlentities($textContent));

        return [$content, [$mediaInfo->video_url]];
    }

    protected function getTextContent($media)
    {
        $textContent = '(no text)';
        //Process the first element, that isn't in the node graph
        if (count($media->edge_media_to_caption->edges) > 0) {
            $textContent = trim($media->edge_media_to_caption->edges[0]->node->text);
        }
        return $textContent;
    }

    protected function getInstagramJSON($uri)
    {
        if (!is_null($this->getInput('u'))) {
            $userId = $this->getInstagramUserId($this->getInput('u'));
            $data = $this->getContents(self::URI .
                                'graphql/query/?query_hash=' .
                                 self::USER_QUERY_HASH .
                                 '&variables={"id"%3A"' .
                                $userId .
                                '"%2C"first"%3A10}');
            return json_decode($data);
        } elseif (!is_null($this->getInput('h'))) {
            $data = $this->getContents(self::URI .
                    'graphql/query/?query_hash=' .
                     self::TAG_QUERY_HASH .
                     '&variables={"tag_name"%3A"' .
                    $this->getInput('h') .
                    '"%2C"first"%3A10}');

            return json_decode($data);
        } else {
            $html = getContents($uri);
            $scriptRegex = '/window\._sharedData = (.*);<\/script>/';

            preg_match($scriptRegex, $html, $matches, PREG_OFFSET_CAPTURE, 0);

            return json_decode($matches[1][0]);
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return $this->getInput('u') . ' - Instagram Bridge';
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return self::URI . urlencode($this->getInput('u')) . '/';
        } elseif (!is_null($this->getInput('h'))) {
            return self::URI . 'explore/tags/' . urlencode($this->getInput('h'));
        } elseif (!is_null($this->getInput('l'))) {
            return self::URI . 'explore/locations/' . urlencode($this->getInput('l'));
        }
        return parent::getURI();
    }

    public function detectParameters($url)
    {
        $params = [];

        // By username
        $regex = '/^(https?:\/\/)?(www\.)?instagram\.com\/([^\/?\n]+)/';

        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'Username';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }
}
