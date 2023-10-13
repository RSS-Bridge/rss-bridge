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
    const URI = 'https://www.youtube.com';
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
    private $feedIconUrl = '';
    // This took from repo BetterVideoRss of VerifiedJoseph.
    const URI_REGEX = '/(https?:\/\/(?:www\.)?(?:[a-zA-Z0-9-.]{2,256}\.[a-z]{2,20})(\:[0-9]{2    ,4})?(?:\/[a-zA-Z0-9@:%_\+.,~#"\'!?&\/\/=\-*]+|\/)?)/ims'; //phpcs:ignore

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
            }
            throw $e;
        }
    }

    private function collectDataInternal()
    {
        $xml = '';
        $html = '';
        $url_feed = '';
        $url_listing = '';

        $username = $this->getInput('u');
        $channel = $this->getInput('c');
        $custom = $this->getInput('custom');

        if ($username) {
            // user and channel
            $request = $username;
            $url_feed = self::URI . '/feeds/videos.xml?user=' . urlencode($request);
            $url_listing = self::URI . '/user/' . urlencode($request) . '/videos';
        } elseif ($channel) {
            $request = $channel;
            $url_feed = self::URI . '/feeds/videos.xml?channel_id=' . urlencode($request);
            $url_listing = self::URI . '/channel/' . urlencode($request) . '/videos';
        } elseif ($custom) {
            $request = $custom;
            $url_listing = self::URI . '/' . urlencode($request) . '/videos';
        }

        $playlist = $this->getInput('p');
        $search = $this->getInput('s');

        $durationMin = $this->getInput('duration_min');
        $durationMax = $this->getInput('duration_max');

        // Whether to discriminate videos by duration
        $filterByDuration = $durationMin || $durationMax;

        if ($url_feed || $url_listing) {
            // user, channel or custom
            $this->feeduri = $url_listing;
            if ($custom) {
                $html = $this->fetch($url_listing);
                $jsonData = $this->extractJsonFromHtml($html);
                // Pluck out the rss feed url
                $url_feed = $jsonData->metadata->channelMetadataRenderer->rssUrl;
                $this->feedIconUrl = $jsonData->metadata->channelMetadataRenderer->avatar->thumbnails[0]->url;
            }
            if ($filterByDuration) {
                if (!$custom) {
                    // Fetch the html page
                    $html = $this->fetch($url_listing);
                    $jsonData = $this->extractJsonFromHtml($html);
                }
                $channel_id = '';
                if (isset($jsonData->contents)) {
                    $channel_id = $jsonData->metadata->channelMetadataRenderer->externalId;
                    $jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[1];
                    $jsonData = $jsonData->tabRenderer->content->richGridRenderer->contents;
                    // $jsonData = $jsonData->itemSectionRenderer->contents[0]->gridRenderer->items;
                    $this->extractItemsFromJsonData($jsonData);
                } else {
                    returnServerError('Unable to get data from YouTube. Username/Channel: ' . $request);
                }
            } else {
                // Fetch the xml feed
                $html = $this->fetch($url_feed);
                $this->extractItemsFromXmlFeed($html);
            }
            $this->feedName = str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
        } elseif ($playlist) {
            // playlist
            // TODO: this mode makes a lot of excess video query requests.
            // To make less requests, we need to cache following dictionary "videoId -> datePublished, duration"
            // This cache will be used to find out, which videos to fetch
            // to make feed of 15 items or more, if there a lot of videos published on that date.
            $request = $playlist;
            $url_feed = self::URI . '/feeds/videos.xml?playlist_id=' . urlencode($request);
            $url_listing = self::URI . '/playlist?list=' . urlencode($request);
            $html = $this->fetch($url_listing);
            $jsonData = $this->extractJsonFromHtml($html);
            // TODO: this method returns only first 100 video items
            // if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
            $jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[0];
            $jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
            $jsonData = $jsonData->contents[0]->playlistVideoListRenderer->contents;
            $item_count = count($jsonData);

            if ($item_count > 15 || $filterByDuration) {
                $this->extractItemsFromJsonData($jsonData);
            } else {
                $xml = $this->fetch($url_feed);
                $this->extractItemsFromXmlFeed($xml);
            }
            $this->feedName = 'Playlist: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
            usort($this->items, function ($item1, $item2) {
                if (!is_int($item1['timestamp']) && !is_int($item2['timestamp'])) {
                    $item1['timestamp'] = strtotime($item1['timestamp']);
                    $item2['timestamp'] = strtotime($item2['timestamp']);
                }
                return $item2['timestamp'] - $item1['timestamp'];
            });
        } elseif ($search) {
            // search
            $request = $search;
            $url_listing = self::URI . '/results?search_query=' . urlencode($request) . '&sp=CAI%253D';
            $html = $this->fetch($url_listing);
            $jsonData = $this->extractJsonFromHtml($html);
            $jsonData = $jsonData->contents->twoColumnSearchResultsRenderer->primaryContents;
            $jsonData = $jsonData->sectionListRenderer->contents;
            foreach ($jsonData as $data) {
                // Search result includes some ads, have to filter them
                if (isset($data->itemSectionRenderer->contents[0]->videoRenderer)) {
                    $jsonData = $data->itemSectionRenderer->contents;
                    break;
                }
            }
            $this->extractItemsFromJsonData($jsonData);
            $this->feeduri = $url_listing;
            $this->feedName = 'Search: ' . $request;
        } else {
            returnClientError("You must either specify either:\n - YouTube
 username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
        }
    }

    private function extractVideoDetails($vid, &$author, &$desc, &$time)
    {
        $url = self::URI . "/watch?v=$vid";
        $html = $this->fetch($url, true);

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

        $jsonData = $this->extractJsonFromHtml($html);
        if (!isset($jsonData->contents)) {
            return;
        }

        $jsonData = $jsonData->contents->twoColumnWatchNextResults->results->results->contents ?? null;
        if (!$jsonData) {
            throw new \Exception('Unable to find json data');
        }
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

        $desc = $videoSecondaryInfo->attributedDescription->content ?? '';

        // Default whitespace chars used by trim + non-breaking spaces (https://en.wikipedia.org/wiki/Non-breaking_space)
        $whitespaceChars = " \t\n\r\0\x0B\u{A0}\u{2060}\u{202F}\u{2007}";
        $descEnhancements = $this->ytBridgeGetVideoDescriptionEnhancements($videoSecondaryInfo, $desc, self::URI, $whitespaceChars);
        foreach ($descEnhancements as $descEnhancement) {
            if (isset($descEnhancement['url'])) {
                $descBefore = mb_substr($desc, 0, $descEnhancement['pos']);
                $descValue = mb_substr($desc, $descEnhancement['pos'], $descEnhancement['len']);
                $descAfter = mb_substr($desc, $descEnhancement['pos'] + $descEnhancement['len'], null);

                // Extended trim for the display value of internal links, e.g.:
                // FAVICON • Video Name
                // FAVICON / @ChannelName
                $descValue = trim($descValue, $whitespaceChars . '•/');

                $desc = sprintf('%s<a href="%s" target="_blank">%s</a>%s', $descBefore, $descEnhancement['url'], $descValue, $descAfter);
            }
        }

        $desc = nl2br($desc);
    }

    private function ytBridgeGetVideoDescriptionEnhancements(
        object $videoSecondaryInfo,
        string $descriptionContent,
        string $baseUrl,
        string $whitespaceChars
    ): array {
        $commandRuns = $videoSecondaryInfo->attributedDescription->commandRuns ?? [];
        if (count($commandRuns) <= 0) {
            return [];
        }

        $enhancements = [];

        $boundaryWhitespaceChars = mb_str_split($whitespaceChars);
        $boundaryStartChars = array_merge($boundaryWhitespaceChars, [':', '-', '(']);
        $boundaryEndChars = array_merge($boundaryWhitespaceChars, [',', '.', "'", ')']);
        $hashtagBoundaryEndChars = array_merge($boundaryEndChars, ['#', '-']);

        $descriptionContentLength = mb_strlen($descriptionContent);

        $minPositionOffset = 0;

        $prevStartPosition = 0;
        $totalLength = 0;
        $maxPositionByStartIndex = [];
        foreach (array_reverse($commandRuns) as $commandRun) {
            $endPosition = $commandRun->startIndex + $commandRun->length;
            if ($endPosition < $prevStartPosition) {
                $totalLength += 1;
            }
            $totalLength += $commandRun->length;
            $maxPositionByStartIndex[$commandRun->startIndex] = $totalLength;
            $prevStartPosition = $commandRun->startIndex;
        }

        foreach ($commandRuns as $commandRun) {
            $commandMetadata = $commandRun->onTap->innertubeCommand->commandMetadata->webCommandMetadata ?? null;
            if (!isset($commandMetadata)) {
                continue;
            }

            $enhancement = null;

            /*
            $commandRun->startIndex can be offset by few positions in the positive direction
            when some multibyte characters (e.g. emojis, but maybe also others) are used in the plain text video description.
            (probably some difference between php and javascript in handling multibyte characters)
            This loop should correct the position in most cases. It searches for the next word (determined by a set of boundary chars) with the expected length.
            Several safeguards ensure that the correct word is chosen. When a link can not be matched,
            everything will be discarded to prevent corrupting the description.
            Hashtags require a different set of boundary chars.
            */
            $isHashtag = $commandMetadata->webPageType === 'WEB_PAGE_TYPE_BROWSE';
            $prevEnhancement = end($enhancements);
            $minPosition = $prevEnhancement === false ? 0 : $prevEnhancement['pos'] + $prevEnhancement['len'];
            $maxPosition = $descriptionContentLength - $maxPositionByStartIndex[$commandRun->startIndex];
            $position = min($commandRun->startIndex - $minPositionOffset, $maxPosition);
            while ($position >= $minPosition) {
                // The link display value can only ever include a new line at the end (which will be removed further below), never in between.
                $newLinePosition = mb_strpos($descriptionContent, "\n", $position);
                if ($newLinePosition !== false && $newLinePosition < $position + ($commandRun->length - 1)) {
                    $position = $newLinePosition - ($commandRun->length - 1);
                    continue;
                }

                $firstChar = mb_substr($descriptionContent, $position, 1);
                $boundaryStart = mb_substr($descriptionContent, $position - 1, 1);
                $boundaryEndIndex = $position + $commandRun->length;
                $boundaryEnd = mb_substr($descriptionContent, $boundaryEndIndex, 1);

                $boundaryStartIsValid = $position === 0 ||
                    in_array($boundaryStart, $boundaryStartChars) ||
                    ($isHashtag && $firstChar === '#');
                $boundaryEndIsValid = $boundaryEndIndex === $descriptionContentLength ||
                    in_array($boundaryEnd, $isHashtag ? $hashtagBoundaryEndChars : $boundaryEndChars);

                if ($boundaryStartIsValid && $boundaryEndIsValid) {
                    $minPositionOffset = $commandRun->startIndex - $position;
                    $enhancement = [
                        'pos' => $position,
                        'len' => $commandRun->length,
                    ];
                    break;
                }

                $position--;
            }

            if (!isset($enhancement)) {
                $this->logger->debug(sprintf('Position %d cannot be corrected in "%s"', $commandRun->startIndex, substr($descriptionContent, 0, 50) . '...'));
                // Skip to prevent the description from becoming corrupted
                continue;
            }

            // $commandRun->length sometimes incorrectly includes the newline as last char
            $lastChar = mb_substr($descriptionContent, $enhancement['pos'] + $enhancement['len'] - 1, 1);
            if ($lastChar === "\n") {
                $enhancement['len'] -= 1;
            }

            $commandUrl = parse_url($commandMetadata->url);
            if ($commandUrl['path'] === '/redirect') {
                parse_str($commandUrl['query'], $commandUrlQuery);
                $enhancement['url'] = urldecode($commandUrlQuery['q']);
            } elseif (isset($commandUrl['host'])) {
                $enhancement['url'] = $commandMetadata->url;
            } else {
                $enhancement['url'] = $baseUrl . $commandMetadata->url;
            }

            $enhancements[] = $enhancement;
        }

        if (count($enhancements) !== count($commandRuns)) {
            // At least one link can not be matched. Discard everything to prevent corrupting the description.
            return [];
        }

        // Sort by position in descending order to be able to safely replace values
        return array_reverse($enhancements);
    }

    private function extractItemsFromXmlFeed($xml)
    {
        $this->feedName = $this->decodeTitle($xml->find('feed > title', 0)->plaintext);

        foreach ($xml->find('entry') as $element) {
            $videoId = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
            if (strpos($videoId, 'googleads') !== false) {
                continue;
            }
            $title = $this->decodeTitle($element->find('title', 0)->plaintext);
            $author = $element->find('name', 0)->plaintext;
            $desc = $element->find('media:description', 0)->innertext;
            $desc = htmlspecialchars($desc);
            $desc = nl2br($desc);
            $desc = preg_replace(self::URI_REGEX, '<a href="$1" target="_blank">$1</a> ', $desc);
            $time = strtotime($element->find('published', 0)->plaintext);
            $this->addItem($videoId, $title, $author, $desc, $time);
        }
    }

    private function fetch($url, $cached = false)
    {
        $header = [
            'Accept-Language: en-US',
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

    private function extractJsonFromHtml($html)
    {
        $scriptRegex = '/var ytInitialData = (.*?);<\/script>/';
        $result = preg_match($scriptRegex, $html, $matches);
        if (! $result) {
            $this->logger->debug('Could not find ytInitialData');
            return null;
        }
        $data = json_decode($matches[1]);
        return $data;
    }

    private function extractItemsFromJsonData($jsonData)
    {
        $duration_min = $this->getInput('duration_min') ?: -1;
        $duration_min = $duration_min * 60;

        $duration_max = $this->getInput('duration_max') ?: INF;
        $duration_max = $duration_max * 60;

        if ($duration_max < $duration_min) {
            returnClientError('Max duration must be greater than min duration!');
        }
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
            $videoId = $wrapper->videoId;
            $title = $wrapper->title->runs[0]->text;
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

            //$durationSeconds = (int) $wrapper->lengthSeconds;
            if ($duration < $duration_min || $duration > $duration_max) {
                continue;
            }
            $this->extractVideoDetails($videoId, $author, $desc, $time);
            $this->addItem($videoId, $title, $author, $desc, $time);
        }
    }

    private function addItem($videoId, $title, $author, $desc, $time, $thumbnail = '')
    {
        $item = [];
        // This should probably be uid?
        $item['id'] = $videoId;
        $item['title'] = $title;
        $item['author'] = $author;
        $item['timestamp'] = $time;
        $item['uri'] = self::URI . '/watch?v=' . $videoId;
        if (!$thumbnail) {
            // Fallback to default thumbnail if there aren't any provided.
            $thumbnail = '0';
        }
        $thumbnailUri = str_replace('/www.', '/img.', self::URI) . '/vi/' . $videoId . '/' . $thumbnail . '.jpg';
        $item['content'] = sprintf('<a href="%s"><img src="%s" /></a><br />%s', $item['uri'], $thumbnailUri, $desc);
        $this->items[] = $item;
    }

    private function decodeTitle($title)
    {
        // convert both &#1234; and &quot; to UTF-8
        return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
    }

    public function getURI()
    {
        if (!is_null($this->getInput('p'))) {
            return static::URI . '/playlist?list=' . $this->getInput('p');
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
        if (empty($this->feedIconUrl)) {
            return parent::getIcon();
        } else {
            return $this->feedIconUrl;
        }
    }
}
