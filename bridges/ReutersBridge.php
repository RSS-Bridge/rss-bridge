<?php

class ReutersBridge extends BridgeAbstract
{
    const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
    const NAME = 'Reuters Bridge';
    const URI = 'https://www.reuters.com';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns news from Reuters';

    private $feedName = self::NAME;
    private $useWireAPI = false;

    /**
     * Wireitem types allowed in the final story output
     */
    const ALLOWED_WIREITEM_TYPES = [
        'story',
        'headlines'
    ];

    /**
     * Wireitem template types allowed in the final story output
     */
    const ALLOWED_TEMPLATE_TYPES = [
        'story',
        'headlines'
    ];

    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from Reuters U.S/International edition',
                'values' => [
                    'Top News' => 'home/topnews',
                    'Fact Check' => 'chan:abtpk0vm',
                    'Entertainment' => 'chan:8ym8q8dl',
                    'Politics' => 'politics',
                    'Wire' => 'wire',
                    'Breakingviews' => '/breakingviews',
                    'World' => [
                        'World' => 'world',
                        'Africa' => '/world/africa',
                        'Americas' => '/world/americas',
                        'Asia-Pacific' => '/world/asia-pacific',
                        'China' => 'china',
                        'europe' => '/world/europe',
                        'India' => '/world/india',
                        'Middle East' => '/world/middle-east',
                        'UK' => 'chan:61leiu7j',
                        'USA News' => 'us',
                        'The Great Reboot' => '/world/the-great-reboot',
                        'Reuters Next' => '/world/reuters-next'
                    ],
                    'Business' => [
                        'Business' => 'business',
                        'Aerospace and Defense' => 'aerospace',
                        'Autos Transportation' => '/business/autos-transportation',
                        'Energy' => 'energy',
                        'Finance' => '/business/finance',
                        'Health' => 'chan:8hw7807a',
                        'Media Telecom' => '/business/media-telecom',
                        'Retail Consumer' => '/business/retail-consumer',
                        'Sustainable Business' => '/business/sustainable-business',
                        'Change Suite' => '/business/change-suite',
                        'Future of Health' => '/business/future-of-health',
                        'Future of Money' => '/business/future-of-money',
                        'Take Five' => '/business/take-five',
                        'Reuters Impact' => '/business/reuters-impact',
                    ],
                    'Legal' => [
                        'Legal' => '/legal',
                        'Government' => '/legal/government',
                        'Legal Industry' => '/legal/legalindustry',
                        'Litigation' => '/legal/litigation',
                        'Transactional' => '/legal/transactional',
                    ],
                    'Markets' => [
                        'Markets' => 'markets',
                        'Asian Markets' => '/markets/asia',
                        'Commodities' => '/markets/commodities',
                        'Currencies' => '/markets/currencies',
                        'Deals' => '/markets/deals',
                        'European Markets' => '/markets/europe',
                        'Funds' => '/markets/fund',
                        'Global Market Data' => '/markets/global-market-data',
                        'Rates & Bonds' => '/markets/rates-bonds',
                        'Stocks' => '/markets/stocks',
                        'U.S Markets' => '/markets/us',
                        'Wealth' => '/markets/wealth',
                        'Macro Matters' => '/markets/macromatters',
                    ],
                    'Technology' => [
                        'Technology' => 'tech',
                        'Disrupted' => '/technology/disrupted',
                        'Reuters Momentum' => '/technology/reuters-momentum',
                    ],
                    'Sports' => [
                        'Sports' => 'sports',
                        'Athletics' => '/lifestyle/sports/athletics',
                        'Cricket' => '/lifestyle/sports/cricket',
                        'Cycling' => '/lifestyle/sports/cycling',
                        'Golf' => '/lifestyle/sports/golf',
                        'Motor Sports' => '/lifestyle/sports/motor-sports',
                        'Soccer' => '/lifestyle/sports/soccer',
                        'Tennis' => '/lifestyle/sports/tennis',
                    ],
                    'Lifestyle' => [
                        'Lifestyle' => 'life',
                        'Oddly Enough' => '/lifestyle/oddly-enough',
                        'Science' => 'science',
                    ]
                ]
            ]
        ]
    ];

    const BACKWARD_COMPATIBILITY = [
        'world' => '/world',
        'china' => '/world/china',
        'chan:61leiu7j' => '/world/uk',
        'us' => '/world/us',
        'business' => '/business',
        'aerospace' => '/business/aerospace-defense',
        'energy' => '/business/energy',
        'environment' => '/business/environment',
        'chan:8hw7807a' => '/business/healthcare-pharmaceuticals',
        'markets' => '/markets',
        'tech' => '/technology',
        'sports' => '/lifestyle/sports',
        'life' => '/lifestyle',
        'science' => '/lifestyle/science',
        'home/topnews' => '/home',
    ];

    const OLD_WIRE_SECTION = [
        'home/topnews',
        'chan:abtpk0vm',
        'chan:8ym8q8dl',
        'politics',
        'wire'
    ];

    /**
     * Takes in data from Reuters Wire API and
     * creates structured data in the form of a list
     * of story information.
     * @param array $data JSON collected from the Reuters Wire API
     */
    private function processData($data)
    {
        /**
         * Gets a list of wire items which are groups of templates
         */
        $reuters_allowed_wireitems = array_filter(
            $data,
            function ($wireitem) {
                return in_array(
                    $wireitem['wireitem_type'],
                    self::ALLOWED_WIREITEM_TYPES
                );
            }
        );

        /*
        * Gets a list of "Templates", which is data containing a story
        */
        $reuters_wireitem_templates = array_reduce(
            $reuters_allowed_wireitems,
            function (array $carry, array $wireitem) {
                $wireitem_templates = $wireitem['templates'];
                return array_merge(
                    $carry,
                    array_filter(
                        $wireitem_templates,
                        function (
                            array $template_data
                        ) {
                            return in_array(
                                $template_data['type'],
                                self::ALLOWED_TEMPLATE_TYPES
                            );
                        }
                    )
                );
            },
            []
        );

        return $reuters_wireitem_templates;
    }

    private function getSectionEndpoint()
    {
        $endpoint = $this->getInput('feed');
        if (isset(self::BACKWARD_COMPATIBILITY[$endpoint])) {
            $endpoint = self::BACKWARD_COMPATIBILITY[$endpoint];
        } elseif (in_array($endpoint, self::OLD_WIRE_SECTION)) {
            $this->useWireAPI = true;
        }
        return $endpoint;
    }

    /**
    * @param string $endpoint - A endpoint is provided could be article URI or ID.
    * @param string $fetch_type - Provide what kind of fetch do you want? Article or Section.
    * @param boolean $is_article_uid {true|false} - A boolean flag to determined if using UID instead of url to fetch.
    * @return string A completed API URL to fetch data
    */
    private function getAPIURL($endpoint, $fetch_type, $is_article_uid = false)
    {
        $base_url = self::URI . '/pf/api/v3/content/fetch/';
        $wire_url = 'https://wireapi.reuters.com/v8';
        switch ($fetch_type) {
            case 'article':
                if ($this->useWireAPI) {
                    return $wire_url . $endpoint;
                }

                $base_query = [
                    'website' => 'reuters',
                ];
                $query = [];

                if ($is_article_uid) {
                    $query = [
                        'id' => $endpoint
                    ];
                } else {
                    $query = [
                        'website_url' => $endpoint,
                    ];
                }

                $query = array_merge($base_query, $query);
                $json_query = json_encode($query);
                return $base_url . 'article-by-id-or-url-v1?query=' . $json_query;
                break;
            case 'section':
                if ($this->useWireAPI) {
                    if (strpos($endpoint, 'chan:') !== false) {
                        // Now checking whether that feed has unique ID or not.
                        $feed_uri = "/feed/rapp/us/wirefeed/$endpoint";
                    } else {
                        $feed_uri = "/feed/rapp/us/tabbar/feeds/$endpoint";
                    }
                    return $wire_url . $feed_uri;
                }
                $query = [
                    'section_id' => $endpoint,
                    'size' => 30,
                    'website' => 'reuters'
                ];

                if ($endpoint != '/home') {
                    $query = array_merge($query, [
                        'fetch_type' => 'section',
                    ]);
                }

                $json_query = json_encode($query);
                return $base_url . 'articles-by-section-alias-or-id-v1?query=' . $json_query;
                break;
        }
        returnServerError('unsupported endpoint');
    }

    private function addStories($title, $content, $timestamp, $author, $url, $category)
    {
        $item = [];
        $item['categories'] = $category;
        $item['author'] = $author;
        $item['content'] = $content;
        $item['title'] = $title;
        $item['timestamp'] = $timestamp;
        $item['uri'] = $url;
        $this->items[] = $item;
    }

    private function getArticle($feed_uri, $is_article_uid = false)
    {
        // This will make another request to API to get full detail of article and author's name.
        $url = $this->getAPIURL($feed_uri, 'article', $is_article_uid);

        try {
            $json = getContents($url);
            $rawData = Json::decode($json);
        } catch (\JsonException $e) {
            return [
                'content' => '',
                'author' => '',
                'category' => '',
                'images' => '',
                'published_at' => ''
            ];
        }
        $article_content = '';
        $authorlist = '';
        $category = [];
        $image_list = [];
        $published_at = '';
        if ($this->useWireAPI) {
            $reuters_wireitems = $rawData['wireitems'];
            $processedData = $this->processData($reuters_wireitems);

            $first = reset($processedData);
            $article_content = $first['story']['body_items'];
            $authorlist = $first['story']['authors'];
            $category = [$first['story']['channel']['name']];
            $image_list = $first['story']['images'];
            $published_at = $first['story']['published_at'];
        } else {
            $article_content = $rawData['result']['content_elements'];
            $authorlist = $rawData['result']['authors'];
            $category = [$rawData['result']['taxonomy']['ads_primary_section']['name']];
            $image_list = [];
            if (!empty($rawData['result']['related_content']['galleries'])) {
                $galleries = $rawData['result']['related_content']['galleries'];
                foreach ($galleries as $gallery) {
                    $image_list = array_merge($image_list, $gallery['content_elements']);
                }
            } elseif (!empty($rawData['result']['related_content']['images'])) {
                $image_list = $rawData['result']['related_content']['images'];
            }
            $published_at = $rawData['result']['published_time'];
        }

        $content_detail = [
            'content' => $this->handleArticleContent($article_content),
            'author' => $this->handleAuthorName($authorlist),
            'category' => $category,
            'images' => $this->handleImage($image_list),
            'published_at' => $published_at
        ];
        return $content_detail;
    }

    private function handleImage($images)
    {
        $img_placeholder = '';

        foreach ($images as $image) {
            // Add more image to article.
            $image_url = $image['url'];
            $image_caption = $image['caption'] ?? $image['alt_text'] ?? $image['subtitle'] ?? '';
            $image_alt_text = '';
            $image_alt_text = $image['alt_text'] ?? $image_caption;
            $img = "<img src=\"$image_url\" alt=\"$image_alt_text\">";
            $img_caption = "<figcaption style=\"text-align: center;\"><i>$image_caption</i></figcaption>";
            $figure = "<figure>$img \t $img_caption</figure>";
            $img_placeholder = $img_placeholder . $figure;
        }

        return $img_placeholder;
    }

    private function handleAuthorName($authors)
    {
        $author = '';
        $counter = 0;
        foreach ($authors as $data) {
            //Formatting author's name.
            $name = $data['name'];
            $counter++;
            if ($counter == count($authors)) {
                $author .= $name;
            } else {
                $author .= $name . ', ';
            }
        }
        return $author;
    }

    private function handleArticleContent($contents)
    {
        $description = '';
        foreach ($contents as $content) {
            $data = '';
            if (isset($content['content'])) {
                $data = $content['content'];
            }
            switch ($content['type']) {
                case 'paragraph':
                    $description = $description . "<p>$data</p>";
                    break;
                case 'heading':
                    $description = $description . "<h3>$data</h3>";
                    break;
                case 'infographics':
                    $description = $description . "<img src=\"$data\">";
                    break;
                case 'inline_items':
                    $item_list = $content['items'];
                    $description = $description . '<p>';
                    foreach ($item_list as $item) {
                        if ($item['type'] == 'text') {
                            $description = $description . $item['content'];
                        } else {
                            $description = $description . $item['symbol'];
                        }
                    }
                    $description = $description . '</p>';
                    break;
                case 'p_table':
                    $description = $description . $content['content'];
                    break;
                case 'upstream_embed':
                    $media_type = $content['media_type'];
                    $cid = $content['cid'];
                    $embed = '';
                    switch ($media_type) {
                        case 'tweet':
                            try {
                                $tweet_url = "https://twitter.com/dummyname/statuses/$cid";
                                $get_embed_url = 'https://publish.twitter.com/oembed?url='
                                                                 . urlencode($tweet_url) .
                                                                '&partner=&hide_thread=false';
                                $oembed_json = json_decode(getContents($get_embed_url), true);
                                $embed .= $oembed_json['html'];
                            } catch (Exception $e) { // In case not found any tweet.
                                $embed .= '';
                            }
                            break;
                        case 'instagram':
                            $url = "https://instagram.com/p/$cid/media/?size=l";
                            $embed .= <<<EOD
<img 
	src="{$url}"
	alt="instagram-image-$cid"
>
EOD;
                            break;
                        case 'youtube':
                            $url = "https://www.youtube.com/embed/$cid";
                            $embed .= <<<EOD
<‌iframe
	width="560" 
	height="315" 
	src="{$url}"
	frameborder="0" 
	allowfullscreen
>
</iframe>
EOD;
                            break;
                    }
                    $description .= $embed;
                    break;
                case 'social_media':
                    if ($content['sub_type'] == 'twitter') {
                        $description .= $content['html'];
                    }
                    break;
                case 'table':
                    $table = '<table>';
                    $theaders = $content['header'] ?? null;
                    if ($theaders) {
                        $tr = '<tr>';
                        foreach ($theaders as $header) {
                            $tr .= '<th>' . $header . '</th>';
                        }
                        $tr .= '</tr>';
                        $table .= $tr;
                    }
                    $rows = $content['rows'];
                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            // some rows are null
                            continue;
                        }
                        $tr = '<tr>';
                        foreach ($row as $data) {
                            $tr .= '<td>' . $data . '</td>';
                        }
                        $tr .= '</tr>';
                        $table .= $tr;
                    }
                    $table .= '</table>';
                    $description .= $table;
                    break;
                case 'image':
                    $description .= $this->handleImage([$content]);
            }
        }

        return $description;
    }

    public function getName()
    {
        return $this->feedName;
    }

    public function collectData()
    {
        $endpoint = $this->getSectionEndpoint();
        $url = $this->getAPIURL($endpoint, 'section');
        $json = getContents($url);
        $data = Json::decode($json);

        $stories = [];
        $section_name = '';
        if ($this->useWireAPI) {
            $reuters_wireitems = $data['wireitems'];
            $section_name = $data['wire_name'];
            $processedData = $this->processData($reuters_wireitems);

            // Merge all articles from Editor's Highlight section into existing array of templates.
            $top_section = reset($processedData);
            if ($top_section['type'] == 'headlines') {
                $top_section = array_shift($processedData);
                $articles = $top_section['headlines'];
                $processedData = array_merge($articles, $processedData);
            }
            $stories = $processedData;
        } else {
            $section_name = $data['result']['section']['name'];
            if (isset($data['arcResult']['articles'])) {
                $stories = $data['arcResult']['articles'];
            } else {
                $stories = $data['result']['articles'];
            }
        }
        $this->feedName = $section_name . ' | Reuters';

        foreach ($stories as $story) {
            $uid = '';
            $author = '';
            $category = [];
            $content = '';
            $title = '';
            $timestamp = '';
            $url = '';
            $article_uri = '';
            $source_type = '';
            if ($this->useWireAPI) {
                $uid = $story['story']['usn'];
                $article_uri = $story['template_action']['api_path'];
                $title = $story['story']['hed'];
                $url = $story['template_action']['url'];
            } else {
                $uid = $story['id'];
                $url = self::URI . $story['canonical_url'];
                $title = $story['title'];
                $article_uri = $story['canonical_url'];
                $source_type = $story['source']['name'];
            }

            // Some article cause unexpected behaviour like redirect to another site not API.
            // Attempt to check article source type to avoid this.
            if (!$this->useWireAPI && $source_type != 'Package') { // Only Reuters PF api have this, Wire don't.
                $author = $this->handleAuthorName($story['authors'] ?? []);
                $timestamp = $story['published_time'];
                $image_placeholder = '';
                if (isset($story['thumbnail'])) {
                    $image_placeholder = $this->handleImage([$story['thumbnail']]);
                }
                $content = $story['description'] . $image_placeholder;
                if (isset($story['primary_section']['name'])) {
                    $category = [$story['primary_section']['name']];
                } else {
                    $category = [];
                }
            } else {
                $content_detail = $this->getArticle($article_uri);
                $description = $content_detail['content'];
                $description = defaultLinkTo($description, $this->getURI());

                $author = $content_detail['author'];
                $images = $content_detail['images'];
                $category = $content_detail['category'];
                $content = "$description  $images";
                $timestamp = $content_detail['published_at'];
            }

            $this->addStories($title, $content, $timestamp, $author, $url, $category);
        }
    }
}
