<?php

class mruac_ItakuBridge extends BridgeAbstract
{
    const NAME = 'Itaku.ee Bridge';
    const URI = 'https://itaku.ee';
    // const CACHE_TIMEOUT = 900; // 15mn
    const CACHE_TIMEOUT = 0;
    const MAINTAINER = 'mruac';
    const DESCRIPTION = 'Bridges for Itaku.ee';
    const PARAMETERS = [
        'Image Search' => [
            'text' => [
                'name' => 'Text to search',
                'title' => 'Search includes title, description and tags.',
                'type' => 'text',
                'defaultValue' => '',
                'exampleValue' => 'Text (incl. tags)'
            ],
            'tags' => [
                'name' => 'Tags to search',
                'title' => 'Space seperated tags to include in search. Prepend with "-" to exclude, "~" for optional.',
                'type' => 'text',
                'defaultValue' => '',
                'exampleValue' => 'tag1 -tag2 ~tag3'
            ],
            'order' => [
                'name' => 'Sort by',
                'type' => 'list',
                'values' => [
                    'Trending' => '-hotness_score',
                    'Newest' => '-date_added',
                    'Oldest' => 'date_added',
                    'Top' => '-num_likes',
                    'Bottom' => 'num_likes'
                ],
                'defaultValue' => '-date_added'
            ],
            'range' => [
                'name' => 'Date range',
                'type' => 'list',
                'values' => [
                    'Today' => 'today',
                    'Yesterday' => 'yesterday',
                    'Past 3 days' => '3_days',
                    'Past week' => 'week',
                    'Past month' => '30_days',
                    'Past year' => '365_days',
                    'All time' => ''
                ],
                'defaultValue' => 'All time'
            ],
            'video_only' => [
                'name' => 'Video only?',
                'type' => 'checkbox'
            ],
            'rating_s' => [
                'name' => 'Include SFW',
                'type' => 'checkbox'
            ],
            'rating_q' => [
                'name' => 'Include Questionable',
                'type' => 'checkbox'
            ],
            'rating_e' => [
                'name' => 'Include NSFW',
                'type' => 'checkbox'
            ]

        ],
        'Post Search' => [
            'tags' => [
                'name' => 'Tags to search',
                'title' => 'Space seperated tags to include in search. Prepend with "-" to exclude, "~" for optional.',
                'type' => 'text',
                'defaultValue' => '',
                'exampleValue' => 'tag1 -tag2 ~tag3'
            ],
            'order' => [
                'name' => 'Sort by',
                'type' => 'list',
                'values' => [
                    'Trending' => '-hotness_score',
                    'Newest' => '-date_added',
                    'Oldest' => 'date_added',
                    'Top' => '-num_likes',
                    'Bottom' => 'num_likes'
                ],
                'defaultValue' => '-date_added'
            ],
            'range' => [
                'name' => 'Date range',
                'type' => 'list',
                'values' => [
                    'Today' => 'today',
                    'Yesterday' => 'yesterday',
                    'Past 3 days' => '3_days',
                    'Past week' => 'week',
                    'Past month' => '30_days',
                    'Past year' => '365_days',
                    'All time' => ''
                ],
                'defaultValue' => 'All time'
            ],
            'text_only' => [
                'name' => 'Only include posts with text?',
                'type' => 'checkbox'
            ],
            'rating_s' => [
                'name' => 'Include SFW',
                'type' => 'checkbox'
            ],
            'rating_q' => [
                'name' => 'Include Questionable',
                'type' => 'checkbox'
            ],
            'rating_e' => [
                'name' => 'Include NSFW',
                'type' => 'checkbox'
            ]
        ],
        'User profile' => [
            'user' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true
            ],
            'user_id' => [
                'name' => 'User ID',
                'type' => 'number',
                'title' => 'User ID, if known.'
            ],
            'reshares' => [
                'name' => 'Include reshares',
                'type' => 'checkbox'
            ],
            'rating_s' => [
                'name' => 'Include SFW',
                'type' => 'checkbox'
            ],
            'rating_q' => [
                'name' => 'Include Questionable',
                'type' => 'checkbox'
            ],
            'rating_e' => [
                'name' => 'Include NSFW',
                'type' => 'checkbox'
            ]
        ],
        'Home feed' => [
            'order' => [
                'name' => 'Sort by',
                'type' => 'list',
                'values' => [
                    'Trending' => '-hotness_score',
                    'Newest' => '-date_added'
                ],
                'defaultValue' => '-date_added'
            ],
            'range' => [
                'name' => 'Date range',
                'type' => 'list',
                'values' => [
                    'Today' => 'today',
                    'Yesterday' => 'yesterday',
                    'Past 3 days' => '3_days',
                    'Past week' => 'week',
                    'Past month' => '30_days',
                    'Past year' => '365_days',
                    'All time' => ''
                ],
                'defaultValue' => 'All time'
            ],
            'reshares' => [
                'name' => 'Include reshares',
                'type' => 'checkbox'
            ],
            'rating_s' => [
                'name' => 'Include SFW',
                'type' => 'checkbox'
            ],
            'rating_q' => [
                'name' => 'Include Questionable',
                'type' => 'checkbox'
            ],
            'rating_e' => [
                'name' => 'Include NSFW',
                'type' => 'checkbox'
            ]
        ]

    ];

    public function collectData()
    {
        if ($this->queriedContext === 'Image Search') {
            $opt = [
                'text' => $this->getInput('text'),
                'optional_tags' => [],
                'negative_tags' => [],
                'required_tags' => [],
                'order' => $this->getInput('order'),
                'range' => $this->getInput('range'),
                'video_only' => $this->getInput('video_only'),
                'rating_s' => $this->getInput('rating_s'),
                'rating_q' => $this->getInput('rating_q'),
                'rating_e' => $this->getInput('rating_e')
            ];

            $tag_arr = explode(' ', $this->getInput('tags'));
            foreach ($tag_arr as $str) {
                switch ($str[0]) {
                    case '-': {
                            $opt['negative_tags'][] = substr($str, 1);
                            break;
                        }
                    case '~': {
                            $opt['optional_tags'][] = substr($str, 1);
                            break;
                        }
                    default: {
                            $opt['required_tags'][] = substr($str, 1);
                            break;
                        }
                }
            }

            $data = $this->getImagesSearch($opt);

            foreach ($data['results'] as $record) {
                $item = $this->getImage($record['id']);
                $this->addItem($item);
            }
        }

        if ($this->queriedContext === 'Post Search') {

            $opt = [
                'optional_tags' => [],
                'negative_tags' => [],
                'required_tags' => [],
                'order' => $this->getInput('order'),
                'range' => $this->getInput('range'),
                'text_only' => $this->getInput('text_only'),
                'rating_s' => $this->getInput('rating_s'),
                'rating_q' => $this->getInput('rating_q'),
                'rating_e' => $this->getInput('rating_e')
            ];

            $tag_arr = explode(' ', $this->getInput('tags'));
            foreach ($tag_arr as $str) {
                switch ($str[0]) {
                    case '-': {
                            $opt['negative_tags'][] = substr($str, 1);
                            break;
                        }
                    case '~': {
                            $opt['optional_tags'][] = substr($str, 1);
                            break;
                        }
                    default: {
                            $opt['required_tags'][] = substr($str, 1);
                            break;
                        }
                }
            }

            $data = $this->getPostsSearch($opt);

            foreach ($data['results'] as $record) {
                $item = $this->getPost($record['id'], $record);
                $this->addItem($item);
            }
        }

        if (
            $this->queriedContext === 'User profile'
            || $this->queriedContext === 'Home feed'
        ) {
            $opt = [
                'reshares' => $this->getInput('reshares'),
                'rating_s' => $this->getInput('rating_s'),
                'rating_q' => $this->getInput('rating_q'),
                'rating_e' => $this->getInput('rating_e')
            ];

            if ($this->queriedContext === 'User profile') {
                $opt['order'] = '-date_added';
                $opt['range'] = '';
                $user_id = $this->getInput('user_id') ?? $this->getOwnerID($this->getInput('user'));

                $data = $this->getFeed(
                    $opt,
                    $user_id
                );
            }

            if ($this->queriedContext === 'Home feed') {
                $opt['order'] = $this->getInput('order');
                $opt['range'] = $this->getInput('range');
                $data = $this->getFeed($opt);
            }

            foreach ($data['results'] as $record) {
                switch ($record['content_type']) {
                    case "reshare": {
                            //get type of reshare and its id
                            $id = $record['content_object']['content_object']['id'];
                            switch ($record['content_object']['content_type']) {
                                case "galleryimage": {
                                        $item = $this->getImage($id);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                                case "commission": {
                                        $item = $this->getCommission($id, $record['content_object']['content_object']);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                                case "post": {
                                        $item = $this->getPost($id, $record['content_object']['content_object']);
                                        $item['title'] = "{$record['owner_username']} shared: {$item['title']}";
                                        break;
                                    }
                            }
                            break;
                        }
                    case "galleryimage": {
                            $item = $this->getImage($record['content_object']['id']);
                            break;
                        }
                    case "commission": {
                            $item = $this->getCommission($record['content_object']['id'], $record['content_object']);
                            break;
                        }
                    case "post": {
                            $item = $this->getPost($record['content_object']['id'], $record['content_object']);
                            break;
                        }
                }

                $this->addItem($item);
            }
        }
    }

    public function getName()
    {
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI;
    }

    private function getImagesSearch(array $opt)
    {
        $url = self::URI . "/api/galleries/images/?by_following=false&date_range={$opt['range']}&ordering={$opt['order']}&is_video={$opt['video_only']}&text={$opt['text']}&visibility=PUBLIC&visibility=PROFILE_ONLY&page=1&page_size=30&format=json";

        if (sizeof($opt['optional_tags']) > 0) {
            foreach ($opt['optional_tags'] as $tag) {
                $url .= "&optional_tags=$tag";
            }
        }
        if (sizeof($opt['negative_tags']) > 0) {
            foreach ($opt['negative_tags'] as $tag) {
                $url .= "&negative_tags=$tag";
            }
        }
        if (sizeof($opt['required_tags']) > 0) {
            foreach ($opt['required_tags'] as $tag) {
                $url .= "&required_tags=$tag";
            }
        }
        if ($opt['rating_s']) {
            $url .= "&maturity_rating=SFW";
        }
        if ($opt['rating_q']) {
            $url .= "&maturity_rating=Questionable";
        }
        if ($opt['rating_e']) {
            $url .= "&maturity_rating=NSFW";
        }

        return $this->getData($url, false, true);
    }


    private function getPostsSearch(array $opt)
    {
        $url = self::URI . "/api/posts/?by_following=false&date_range={$opt['range']}&ordering={$opt['order']}&visibility=PUBLIC&visibility=PROFILE_ONLY&page=1&page_size=30&format=json";

        if (sizeof($opt['optional_tags']) > 0) {
            foreach ($opt['optional_tags'] as $tag) {
                $url .= "&optional_tags=$tag";
            }
        }
        if (sizeof($opt['negative_tags']) > 0) {
            foreach ($opt['negative_tags'] as $tag) {
                $url .= "&negative_tags=$tag";
            }
        }
        if (sizeof($opt['required_tags']) > 0) {
            foreach ($opt['required_tags'] as $tag) {
                $url .= "&required_tags=$tag";
            }
        }
        if ($opt['rating_s']) {
            $url .= "&maturity_rating=SFW";
        }
        if ($opt['rating_q']) {
            $url .= "&maturity_rating=Questionable";
        }
        if ($opt['rating_e']) {
            $url .= "&maturity_rating=NSFW";
        }

        return $this->getData($url, false, true);
    }

    private function getFeed(array $opt, $ownerID = null)
    {
        $url = self::URI . "/api/feed/?date_range={$opt['range']}&ordering={$opt['order']}&page=1&page_size=30&format=json";

        if (is_null($ownerID)) {
            $url .= "&visibility=PUBLIC&by_following=false";
        } else {
            $url .= "&owner={$ownerID}";
        }

        if (!$opt['reshares']) {
            $url .= "&hide_reshares=true";
        }
        if ($opt['rating_s']) {
            $url .= "&maturity_rating=SFW";
        }
        if ($opt['rating_q']) {
            $url .= "&maturity_rating=Questionable";
        }
        if ($opt['rating_e']) {
            $url .= "&maturity_rating=NSFW";
        }

        return $this->getData($url, false, true);
    }

    private function getOwnerID($username)
    {
        $url = self::URI . "/api/user_profiles/{$username}/?format=json";
        $data = $this->getData($url, true, true)
            or returnServerError("Could not load $url");

        return $data['owner'];
    }

    private function getPost($id, array $metadata = null)
    {
        $uri = self::URI . '/posts/' . $id;
        $url = self::URI . '/api/posts/' . $id . '/?format=json';
        $data = $metadata ?? $this->getData($url, true, true)
            or returnServerError("Could not load $url");

        $content_str = nl2br($data['content']);
        $content = "<p>{$content_str}</p><br/>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (array_key_exists('tags', $data) && sizeof($data['tags']) > 0) {
            $tag_types = [
                'ARTIST' => '',
                'COPYRIGHT' => '',
                'CHARACTER' => '',
                'SPECIES' => '',
                'GENERAL' => '',
                'META' => ''
            ];
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/tags/' . $tag['id'];
                $str = "<a href=\"{$url}\">#{$tag['name']}</a> ";
                $tag_types[$tag['tag_type']] .= $str;
            }

            foreach ($tag_types as $type => $str) {
                if (strlen($str) > 0) {
                    $content .= "🏷 <b>{$type}:</b> {$str}<br/>";
                }
            }
        }

        if (sizeof($data['folders']) > 0) {
            $content .= "📁 In Folder(s): ";
            foreach ($data['folders'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/posts/' . $folder['id'];
                $content .= "<a href=\"{$url}\">#{$folder['title']}</a> ";
            }
        }

        $content .= "<hr/>";
        if (sizeof($data['gallery_images']) > 0) {
            foreach ($data['gallery_images'] as $media) {
                $title = $media['title'];
                $url = self::URI . '/images/' . $media['id'];
                $src = $media['image_xl'];
                $content .= "<p>";
                $content .= "<a href=\"{$url}\"><b>{$title}</b></a><br/>";
                if ($media['is_thumbnail_for_video']) {
                    $url = self::URI . '/api/galleries/images/' . $media['id'] . '/?format=json';
                    $media_data = $this->getData($url, true, true)
                        or returnServerError("Could not load $url");
                    $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$media['image_xl']}\"/>";
                } else {
                    $content .= "<a href=\"{$url}\"><img src=\"{$src}\"></a>";
                }
                $content .= "</p><br/>";
            }
        }

        return [
            'uri' => $uri,
            'title' => $data['title'],
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['post'],
            'uid' => $uri
        ];
    }

    private function getCommission($id, array $metadata = null)
    {
        $url = self::URI . '/api/commissions/' . $id . '/?format=json';
        $uri = self::URI . '/commissions/' . $id;
        // Debug::log(var_dump($metadata));
        $data = $metadata ?? $this->getData($url, true, true)
            or returnServerError("Could not load $url");

        $content_str = nl2br($data['description']);
        $content = "<p>{$content_str}</p><br>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (array_key_exists('tags', $data) && sizeof($data['tags']) > 0) {
            // $content .= "🏷 Tag(s): ";
            $tag_types = [
                'ARTIST' => '',
                'COPYRIGHT' => '',
                'CHARACTER' => '',
                'SPECIES' => '',
                'GENERAL' => '',
                'META' => ''
            ];
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/tags/' . $tag['id'];
                $str = "<a href=\"{$url}\">#{$tag['name']}</a> ";
                $tag_types[$tag['tag_type']] .= $str;
            }

            foreach ($tag_types as $type => $str) {
                if (strlen($str) > 0) {
                    $content .= "🏷 <b>{$type}:</b> {$str}<br/>";
                }
            }
        }

        if (array_key_exists('reference_gallery_sections', $data) && sizeof($data['reference_gallery_sections']) > 0) {
            $content .= "📁 Example folder(s): ";
            foreach ($data['folders'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/gallery/' . $folder['id'];
                $folder_name = $folder['title'];
                if (!is_null($folder['group'])) {
                    $folder_name = $folder['group']['title'] . '/' . $folder_name;
                }
                $content .= "<a href=\"{$url}\">#{$folder_name}</a> ";
            }
        }

        $content .= "<hr/>";
        if (!is_null($data['thumbnail_detail'])) {
            $content .= "<p>";
            $content .= "<a href=\"{$uri}\"><b>{$data['thumbnail_detail']['title']}</b></a><br/>";
            if ($data['thumbnail_detail']['is_thumbnail_for_video']) {
                $url = self::URI . '/api/galleries/images/' . $data['thumbnail_detail']['id'] . '/?format=json';
                $media_data = $this->getData($url, true, true)
                    or returnServerError("Could not load $url");
                $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$data['thumbnail_detail']['image_lg']}\"/>";
            } else {
                $content .= "<a href=\"{$uri}\"><img src=\"{$data['thumbnail_detail']['image_lg']}\"></a>";
            }

            $content .= "</p>";
        }

        return [
            'uri' => $uri,
            'title' => "{$data['comm_type']}: {$data['title']}",
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['commission', $data['comm_type']],
            'uid' => $uri
        ];
    }

    private function getImage($id /* array $metadata = null */) //$metadata disabled due to no essential information available in ./api/feed/ or ./api/galleries/images/ results.
    {
        $uri = self::URI . '/images/' . $id;
        $url = self::URI . '/api/galleries/images/' . $id . '/?format=json';
        $data = /* $metadata ?? */ $this->getData($url, true, true)
            or returnServerError("Could not load $url");

        $content_str = nl2br($data['description']);
        $content = "<p>{$content_str}</p><br/>"; //TODO: Add link and itaku user mention detection and convert into links.

        if (array_key_exists('tags', $data) && sizeof($data['tags']) > 0) {
            // $content .= "🏷 Tag(s): ";
            $tag_types = [
                'ARTIST' => '',
                'COPYRIGHT' => '',
                'CHARACTER' => '',
                'SPECIES' => '',
                'GENERAL' => '',
                'META' => ''
            ];
            foreach ($data['tags'] as $tag) {
                $url = self::URI . '/tags/' . $tag['id'];
                $str = "<a href=\"{$url}\">#{$tag['name']}</a> ";
                $tag_types[$tag['tag_type']] .= $str;
            }

            foreach ($tag_types as $type => $str) {
                if (strlen($str) > 0) {
                    $content .= "🏷 <b>{$type}:</b> {$str}<br/>";
                }
            }
        }

        if (array_key_exists('sections', $data) && sizeof($data['sections']) > 0) {
            $content .= "📁 In Folder(s): ";
            foreach ($data['sections'] as $folder) {
                $url = self::URI . '/profile/' . $data['owner_username'] . '/gallery/' . $folder['id'];
                $folder_name = $folder['title'];
                if (!is_null($folder['group'])) {
                    $folder_name = $folder['group']['title'] . '/' . $folder_name;
                }
                $content .= "<a href=\"{$url}\">#{$folder_name}</a> ";
            }
        }

        $content .= "<hr/>";

        if (array_key_exists('is_thumbnail_for_video', $data)) {
            $url = self::URI . '/api/galleries/images/' . $data['id'] . '/?format=json';
            $media_data = $this->getData($url, true, true)
                or returnServerError("Could not load $url");
            $content .= "<video controls src=\"{$media_data['video']['video']}\" poster=\"{$data['image_xl']}\"/>";
        } else {
            if (array_key_exists('video', $data) && is_null($data['video'])) {
                $content .= "<a href=\"{$uri}\"><img src=\"{$data['image_xl']}\"></a>";
            } else {
                $content .= "<video controls src=\"{$data['video']['video']}\" poster=\"{$data['image_xl']}\"/>";
            }
        }

        return [
            'uri' => $uri,
            'title' => $data['title'],
            'timestamp' => $data['date_added'],
            'author' =>  $data['owner_username'],
            'content' => $content,
            'categories' => ['image'],
            'uid' => $uri
        ];
    }

    private function getData(string $url, bool $cache = false, bool $getJSON = false, array $httpHeaders = [], array $curlOptions = [])
    {
        // Debug::log($url);
        if ($getJSON) { //get JSON object
            if ($cache) {
                $data = $this->loadCacheValue($url, 86400); // 24 hours
                if (is_null($data)) {
                    $data = getContents($url, $httpHeaders, $curlOptions) or returnServerError("Could not load $url");
                    $this->saveCacheValue($url, $data);
                }
            } else {
                $data = getContents($url, $httpHeaders, $curlOptions) or returnServerError("Could not load $url");
            }
            return json_decode($data, true);
        } else { //get simpleHTMLDOM object
            if ($cache) {
                $html = getSimpleHTMLDOMCached($url, 86400); // 24 hours
            } else {
                $html = getSimpleHTMLDOM($url);
            }
            $html = defaultLinkTo($html, $url);
            return $html;
        }
    }

    private function addItem($item)
    {
        if (is_null($item)) {
            return;
        }

        if (is_array($item) || is_object($item)) {
            $this->items[] = $item;
        } else {
            returnServerError("Incorrectly parsed item. Check the code!\nType: " . gettype($item) . "\nprint_r(item:)\n" . print_r($item));
        }
    }
}
