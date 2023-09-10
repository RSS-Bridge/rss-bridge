<?php

/**
* RssBridgeYoutube
* Returns the newest videos
* WARNING: to parse big playlists (over ~90 videos), you need to edit simple_html_dom.php:
* change: define('MAX_FILE_SIZE', 600000);
* into:   define('MAX_FILE_SIZE', 900000);  (or more)
*/
class YoutubeBridge extends BridgeAbstract
{
    const NAME = 'YouTube Bridge';
    const URI = 'https://www.youtube.com/';
    const CACHE_TIMEOUT = 10800; // 3h
    const DESCRIPTION = 'Returns the 10 newest videos by username/channel/playlist or search';

    const PARAMETERS = [
        'By username' => [
            'u' => [
                'name' => 'username',
                'exampleValue' => 'LinusTechTips',
                'required' => true
            ]
        ],
        'By channel id' => [
            'c' => [
                'name' => 'channel id',
                'exampleValue' => 'UCw38-8_Ibv_L6hlKChHO9dQ',
                'required' => true
            ]
        ],
        'By custom name' => [
            'custom' => [
                'name' => 'custom name',
                'exampleValue' => 'LinusTechTips',
                'required' => true
            ]
        ],
        'By playlist Id' => [
            'p' => [
                'name' => 'playlist id',
                'exampleValue' => 'PL8mG-RkN2uTzJc8N0EoyhdC54prvBBLpj',
                'required' => true
            ]
        ],
        'Search result' => [
            's' => [
                'name' => 'search keyword',
                'exampleValue' => 'LinusTechTips',
                'required' => true
            ],
            'pa' => [
                'name' => 'page',
                'type' => 'number',
                'title' => 'This option is not work anymore, as YouTube will always return the same page',
                'exampleValue' => 1
            ]
        ],
        'global' => [
            'duration_min' => [
                'name' => 'min. duration (minutes)',
                'type' => 'number',
                'title' => 'Minimum duration for the video in minutes',
                'exampleValue' => 5
            ],
            'duration_max' => [
                'name' => 'max. duration (minutes)',
                'type' => 'number',
                'title' => 'Maximum duration for the video in minutes',
                'exampleValue' => 10
            ]
        ]
    ];

    private $feedName = '';
    private $feeduri = '';
    private $channel_name = '';
    // This took from repo BetterVideoRss of VerifiedJoseph.
    const URI_REGEX = '/(https?:\/\/(?:www\.)?(?:[a-zA-Z0-9-.]{2,256}\.[a-z]{2,20})(\:[0-9]{2    ,4})?(?:\/[a-zA-Z0-9@:%_\+.,~#"\'!?&\/\/=\-*]+|\/)?)/ims'; //phpcs:ignore
    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
    }

    private function collectDataInternal()
    {
        $xml = '';
        $html = '';
        $url_feed = '';
        $url_listing = '';

        if ($this->getInput('u')) {
            /* User and Channel modes */
            $this->request = $this->getInput('u');
            $url_feed = self::URI . 'feeds/videos.xml?user=' . urlencode($this->request);
            $url_listing = self::URI . 'user/' . urlencode($this->request) . '/videos';
        } elseif ($this->getInput('c')) {
            $this->request = $this->getInput('c');
            $url_feed = self::URI . 'feeds/videos.xml?channel_id=' . urlencode($this->request);
            $url_listing = self::URI . 'channel/' . urlencode($this->request) . '/videos';
        } elseif ($this->getInput('custom')) {
            $this->request = $this->getInput('custom');
            $url_listing = self::URI . urlencode($this->request) . '/videos';
        }

        if (!empty($url_feed) || !empty($url_listing)) {
            $this->feeduri = $url_listing;
            if (!empty($this->getInput('custom'))) {
                $html = $this->ytGetSimpleHTMLDOM($url_listing);
                $jsonData = $this->getJSONData($html);
                $url_feed = $jsonData->metadata->channelMetadataRenderer->rssUrl;
                $this->iconURL = $jsonData->metadata->channelMetadataRenderer->avatar->thumbnails[0]->url;
            }
            if (!$this->skipFeeds()) {
                $html = $this->ytGetSimpleHTMLDOM($url_feed);
                $this->ytBridgeParseXmlFeed($html);
            } else {
                if (empty($this->getInput('custom'))) {
                    $html = $this->ytGetSimpleHTMLDOM($url_listing);
                    $jsonData = $this->getJSONData($html);
                }
                $channel_id = '';
                if (isset($jsonData->contents)) {
                    $channel_id = $jsonData->metadata->channelMetadataRenderer->externalId;
                    $jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[1];
                    $jsonData = $jsonData->tabRenderer->content->richGridRenderer->contents;
                    // $jsonData = $jsonData->itemSectionRenderer->contents[0]->gridRenderer->items;
                    $this->parseJSONListing($jsonData);
                } else {
                    returnServerError('Unable to get data from YouTube. Username/Channel: ' . $this->request);
                }
            }
            $this->feedName = str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
        } elseif ($this->getInput('p')) {
            /* playlist mode */
            // TODO: this mode makes a lot of excess video query requests.
            // To make less requests, we need to cache following dictionary "videoId -> datePublished, duration"
            // This cache will be used to find out, which videos to fetch
            // to make feed of 15 items or more, if there a lot of videos published on that date.
            $this->request = $this->getInput('p');
            $url_feed = self::URI . 'feeds/videos.xml?playlist_id=' . urlencode($this->request);
            $url_listing = self::URI . 'playlist?list=' . urlencode($this->request);
            $html = $this->ytGetSimpleHTMLDOM($url_listing);
            $jsonData = $this->getJSONData($html);
            // TODO: this method returns only first 100 video items
            // if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
            $jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[0];
            $jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
            $jsonData = $jsonData->contents[0]->playlistVideoListRenderer->contents;
            $item_count = count($jsonData);

            if ($item_count <= 15 && !$this->skipFeeds() && ($xml = $this->ytGetSimpleHTMLDOM($url_feed))) {
                $this->ytBridgeParseXmlFeed($xml);
            } else {
                $this->parseJSONListing($jsonData);
            }
            $this->feedName = 'Playlist: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
            usort($this->items, function ($item1, $item2) {
                if (!is_int($item1['timestamp']) && !is_int($item2['timestamp'])) {
                    $item1['timestamp'] = strtotime($item1['timestamp']);
                    $item2['timestamp'] = strtotime($item2['timestamp']);
                }
                return $item2['timestamp'] - $item1['timestamp'];
            });
        } elseif ($this->getInput('s')) {
            /* search mode */
            $this->request = $this->getInput('s');
            $url_listing = self::URI
                . 'results?search_query='
                . urlencode($this->request)
                . '&sp=CAI%253D';

            $html = $this->ytGetSimpleHTMLDOM($url_listing);

            $jsonData = $this->getJSONData($html);
            $jsonData = $jsonData->contents->twoColumnSearchResultsRenderer->primaryContents;
            $jsonData = $jsonData->sectionListRenderer->contents;
            foreach ($jsonData as $data) {
                // Search result includes some ads, have to filter them
                if (isset($data->itemSectionRenderer->contents[0]->videoRenderer)) {
                    $jsonData = $data->itemSectionRenderer->contents;
                    break;
                }
            }
            $this->parseJSONListing($jsonData);
            $this->feeduri = $url_listing;
            $this->feedName = 'Search: ' . $this->request;
        } else {
            /* no valid mode */
            returnClientError("You must either specify either:\n - YouTube
 username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
        }
    }

    public function collectData()
    {
        $cacheKey = 'youtube_rate_limit';
        if ($this->cache->get($cacheKey)) {
            throw new HttpException('429 Too Many Requests', 429);
        }
        try {
            $this->collectDataInternal();
        } catch (HttpException $e) {
            if ($e->getCode() === 429) {
                $this->cache->set($cacheKey, true, 60 * 16);
                throw $e;
            }
        }
    }

    private function ytBridgeQueryVideoInfo($vid, &$author, &$desc, &$time)
    {
        $html = $this->ytGetSimpleHTMLDOM(self::URI . "watch?v=$vid", true);

        // Skip unavailable videos
        if (strpos($html->innertext, 'IS_UNAVAILABLE_PAGE') !== false) {
            return;
        }

        $elAuthor = $html->find('span[itemprop=author] > link[itemprop=name]', 0);
        if (!is_null($elAuthor)) {
            $author = $elAuthor->getAttribute('content');
        }

        $elDatePublished = $html->find('meta[itemprop=datePublished]', 0);
        if (!is_null($elDatePublished)) {
            $time = strtotime($elDatePublished->getAttribute('content'));
        }

        $jsonData = $this->getJSONData($html);
        if (! isset($jsonData->contents)) {
            return;
        }

        $jsonData = $jsonData->contents->twoColumnWatchNextResults->results->results->contents;
        $videoSecondaryInfo = null;
        foreach ($jsonData as $item) {
            if (isset($item->videoSecondaryInfoRenderer)) {
                $videoSecondaryInfo = $item->videoSecondaryInfoRenderer;
                break;
            }
        }
        if (!$videoSecondaryInfo) {
            returnServerError('Could not find videoSecondaryInfoRenderer. Error at: ' . $vid);
        }

        if (isset($videoSecondaryInfo->description)) {
            foreach ($videoSecondaryInfo->description->runs as $description) {
                if (isset($description->navigationEndpoint)) {
                    $metadata = $description->navigationEndpoint->commandMetadata->webCommandMetadata;
                    $web_type = $metadata->webPageType;
                    $url = $metadata->url;
                    $text = '';
                    switch ($web_type) {
                        case 'WEB_PAGE_TYPE_UNKNOWN':
                            $url_components = parse_url($url);
                            if (isset($url_components['query']) && strpos($url_components['query'], '&q=') !== false) {
                                parse_str($url_components['query'], $params);
                                $url = urldecode($params['q']);
                            }
                            $text = $url;
                            break;
                        case 'WEB_PAGE_TYPE_WATCH':
                        case 'WEB_PAGE_TYPE_BROWSE':
                            $url = 'https://www.youtube.com' . $url;
                            $text = $description->text;
                            break;
                    }
                    $desc .= "<a href=\"$url\" target=\"_blank\">$text</a>";
                } else {
                    $desc .= nl2br($description->text);
                }
            }
        }
    }

    private function ytBridgeAddItem($vid, $title, $author, $desc, $time, $thumbnail = '')
    {
        $item = [];
        $item['id'] = $vid;
        $item['title'] = $title;
        $item['author'] = $author;
        $item['timestamp'] = $time;
        $item['uri'] = self::URI . 'watch?v=' . $vid;
        if (!$thumbnail) {
            // Fallback to default thumbnail if there aren't any provided.
            $thumbnail = '0';
        }
        $thumbnailUri = str_replace('/www.', '/img.', self::URI) . 'vi/' . $vid . '/' . $thumbnail . '.jpg';
        $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br />' . $desc;
        $this->items[] = $item;
    }

    private function ytBridgeParseXmlFeed($xml)
    {
        foreach ($xml->find('entry') as $element) {
            $title = $this->ytBridgeFixTitle($element->find('title', 0)->plaintext);
            $author = $element->find('name', 0)->plaintext;
            $desc = $element->find('media:description', 0)->innertext;

            // Make sure the description is easy on the eye :)
            $desc = htmlspecialchars($desc);
            $desc = nl2br($desc);
            $desc = preg_replace(
                self::URI_REGEX,
                '<a href="$1" target="_blank">$1</a> ',
                $desc
            );

            $vid = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
            $time = strtotime($element->find('published', 0)->plaintext);
            if (strpos($vid, 'googleads') === false) {
                $this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
            }
        }
        $this->feedName = $this->ytBridgeFixTitle($xml->find('feed > title', 0)->plaintext);  // feedName will be used by getName()
    }

    private function ytBridgeFixTitle($title)
    {
        // convert both &#1234; and &quot; to UTF-8
        return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
    }

    private function ytGetSimpleHTMLDOM($url, $cached = false)
    {
        $header = [
            'Accept-Language: en-US'
        ];
        $opts = [];
        $lowercase = true;
        $forceTagsClosed = true;
        $target_charset = DEFAULT_TARGET_CHARSET;
        $stripRN = false;
        $defaultBRText = DEFAULT_BR_TEXT;
        $defaultSpanText = DEFAULT_SPAN_TEXT;
        if ($cached) {
            return getSimpleHTMLDOMCached(
                $url,
                86400,
                $header,
                $opts,
                $lowercase,
                $forceTagsClosed,
                $target_charset,
                $stripRN,
                $defaultBRText,
                $defaultSpanText
            );
        }
        return getSimpleHTMLDOM(
            $url,
            $header,
            $opts,
            $lowercase,
            $forceTagsClosed,
            $target_charset,
            $stripRN,
            $defaultBRText,
            $defaultSpanText
        );
    }

    private function getJSONData($html)
    {
        $scriptRegex = '/var ytInitialData = (.*?);<\/script>/';
        $result = preg_match($scriptRegex, $html, $matches);
        if (! $result) {
            Logger::debug('Could not find ytInitialData');
            return null;
        }
        return json_decode($matches[1]);
    }

    private function parseJSONListing($jsonData)
    {
        $duration_min = $this->getInput('duration_min') ?: -1;
        $duration_min = $duration_min * 60;

        $duration_max = $this->getInput('duration_max') ?: INF;
        $duration_max = $duration_max * 60;

        if ($duration_max < $duration_min) {
            returnClientError('Max duration must be greater than min duration!');
        }

        // $vid_list = '';

        foreach ($jsonData as $item) {
            $wrapper = null;
            if (isset($item->gridVideoRenderer)) {
                $wrapper = $item->gridVideoRenderer;
            } elseif (isset($item->videoRenderer)) {
                $wrapper = $item->videoRenderer;
            } elseif (isset($item->playlistVideoRenderer)) {
                $wrapper = $item->playlistVideoRenderer;
            } elseif (isset($item->richItemRenderer)) {
                $wrapper = $item->richItemRenderer->content->videoRenderer;
            } else {
                continue;
            }

            $vid = $wrapper->videoId;
            $title = $wrapper->title->runs[0]->text;
            if (isset($wrapper->ownerText)) {
                $this->channel_name = $wrapper->ownerText->runs[0]->text;
            } elseif (isset($wrapper->shortBylineText)) {
                $this->channel_name = $wrapper->shortBylineText->runs[0]->text;
            }

            $author = '';
            $desc = '';
            $time = '';

            // The duration comes in one of the formats:
            // hh:mm:ss / mm:ss / m:ss
            // 01:03:30 / 15:06 / 1:24
            $durationText = 0;
            if (isset($wrapper->lengthText)) {
                $durationText = $wrapper->lengthText->simpleText;
            } else {
                foreach ($wrapper->thumbnailOverlays as $overlay) {
                    if (isset($overlay->thumbnailOverlayTimeStatusRenderer)) {
                        $durationText = $overlay->thumbnailOverlayTimeStatusRenderer->text;
                        break;
                    }
                }
            }

            if (is_string($durationText)) {
                if (preg_match('/([\d]{1,2})\:([\d]{1,2})\:([\d]{2})/', $durationText)) {
                    $durationText = preg_replace('/([\d]{1,2})\:([\d]{1,2})\:([\d]{2})/', '$1:$2:$3', $durationText);
                } else {
                    $durationText = preg_replace('/([\d]{1,2})\:([\d]{2})/', '00:$1:$2', $durationText);
                }
                sscanf($durationText, '%d:%d:%d', $hours, $minutes, $seconds);
                $duration = $hours * 3600 + $minutes * 60 + $seconds;
                if ($duration < $duration_min || $duration > $duration_max) {
                    continue;
                }
            }

            // $vid_list .= $vid . ',';
            $this->ytBridgeQueryVideoInfo($vid, $author, $desc, $time);
            $this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
        }
    }

    private function skipFeeds()
    {
        return ($this->getInput('duration_min') || $this->getInput('duration_max'));
    }

    public function getURI()
    {
        if (!is_null($this->getInput('p'))) {
            return static::URI . 'playlist?list=' . $this->getInput('p');
        } elseif ($this->feeduri) {
            return $this->feeduri;
        }

        return parent::getURI();
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By username':
            case 'By channel id':
            case 'By custom name':
            case 'By playlist Id':
            case 'Search result':
                return htmlspecialchars_decode($this->feedName) . ' - YouTube';
            default:
                return parent::getName();
        }
    }

    public function getIcon()
    {
        if (empty($this->iconURL)) {
            return parent::getIcon();
        } else {
            return $this->iconURL;
        }
    }
}
