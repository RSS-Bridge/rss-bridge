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
    const CACHE_TIMEOUT = 60 * 60 * 3;
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
        $html = '';
        $url_feed = '';
        $url_listing = '';

        $username = $this->getInput('u');
        $channel = $this->getInput('c');
        $custom = $this->getInput('custom');
        $playlist = $this->getInput('p');
        $search = $this->getInput('s');

        $durationMin = $this->getInput('duration_min');
        $durationMax = $this->getInput('duration_max');

        // Whether to discriminate videos by duration
        $filterByDuration = $durationMin || $durationMax;

        if ($username) {
            // user and channel
            $url_feed = self::URI . '/feeds/videos.xml?user=' . urlencode($username);
            $url_listing = self::URI . '/user/' . urlencode($username) . '/videos';
        } elseif ($channel) {
            $url_feed = self::URI . '/feeds/videos.xml?channel_id=' . urlencode($channel);
            $url_listing = self::URI . '/channel/' . urlencode($channel) . '/videos';
        } elseif ($custom) {
            $url_listing = self::URI . '/' . urlencode($custom) . '/videos';
        }

        if ($url_feed || $url_listing) {
            // user, channel or custom
            $this->feeduri = $url_listing;
            if ($custom) {
                // Extract the feed url for the custom name
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
                    $this->fetchItemsFromFromJsonData($jsonData);
                } else {
                    returnServerError('Unable to get data from YouTube');
                }
            } else {
                // Fetch the xml feed
                $html = $this->fetch($url_feed);
                $this->extractItemsFromXmlFeed($html);
            }
            $this->feedName = str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
        } elseif ($playlist) {
            // playlist
            $url_feed = self::URI . '/feeds/videos.xml?playlist_id=' . urlencode($playlist);
            $url_listing = self::URI . '/playlist?list=' . urlencode($playlist);
            $html = $this->fetch($url_listing);
            $jsonData = $this->extractJsonFromHtml($html);
            // TODO: this method returns only first 100 video items
            // if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
            $jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[0];
            $jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
            $jsonData = $jsonData->contents[0]->playlistVideoListRenderer->contents;
            $item_count = count($jsonData);

            if ($item_count > 15 || $filterByDuration) {
                $this->fetchItemsFromFromJsonData($jsonData);
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
            $url_listing = self::URI . '/results?search_query=' . urlencode($search) . '&sp=CAI%253D';
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
            $this->fetchItemsFromFromJsonData($jsonData);
            $this->feeduri = $url_listing;
            $this->feedName = 'Search: ' . $search;
        } else {
            returnClientError("You must either specify either:\n - YouTube username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
        }
    }

    private function fetchVideoDetails($videoId, &$author, &$description, &$timestamp)
    {
        $url = self::URI . "/watch?v=$videoId";
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
            $timestamp = strtotime($elDatePublished->getAttribute('content'));
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
            returnServerError('Could not find videoSecondaryInfoRenderer. Error at: ' . $videoId);
        }

        $description = $videoSecondaryInfo->attributedDescription->content ?? '';

        // Default whitespace chars used by trim + non-breaking spaces (https://en.wikipedia.org/wiki/Non-breaking_space)
        $whitespaceChars = " \t\n\r\0\x0B\u{A0}\u{2060}\u{202F}\u{2007}";
        $descEnhancements = $this->ytBridgeGetVideoDescriptionEnhancements($videoSecondaryInfo, $description, self::URI, $whitespaceChars);
        foreach ($descEnhancements as $descEnhancement) {
            if (isset($descEnhancement['url'])) {
                $descBefore = mb_substr($description, 0, $descEnhancement['pos']);
                $descValue = mb_substr($description, $descEnhancement['pos'], $descEnhancement['len']);
                $descAfter = mb_substr($description, $descEnhancement['pos'] + $descEnhancement['len'], null);

                // Extended trim for the display value of internal links, e.g.:
                // FAVICON • Video Name
                // FAVICON / @ChannelName
                $descValue = trim($descValue, $whitespaceChars . '•/');

                $description = sprintf('%s<a href="%s" target="_blank">%s</a>%s', $descBefore, $descEnhancement['url'], $descValue, $descAfter);
            }
        }
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

    private function fetch($url, bool $cache = false)
    {
        $header = ['Accept-Language: en-US'];
        $ttl = 86400 * 3; // 3d
        $stripNewlines = false;
        if ($cache) {
            return getSimpleHTMLDOMCached($url, $ttl, $header, [], true, true, DEFAULT_TARGET_CHARSET, $stripNewlines);
        }
        return getSimpleHTMLDOM($url, $header, [], true, true, DEFAULT_TARGET_CHARSET, $stripNewlines);
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

    private function fetchItemsFromFromJsonData($jsonData)
    {
        $minimumDurationSeconds = ($this->getInput('duration_min') ?: -1) * 60;
        $maximumDurationSeconds = ($this->getInput('duration_max') ?: INF) * 60;

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

            // 01:03:30 | 15:06 | 1:24
            $lengthText = $wrapper->lengthText->simpleText ?? null;
            // 6,875 views
            $viewCount = $wrapper->viewCountText->simpleText ?? null;
            // Dc645M8Het8
            $videoId = $wrapper->videoId;
            // Jumbo frames - transfer more data faster!
            $title = $wrapper->title->runs[0]->text ?? $wrapper->title->accessibility->accessibilityData->label ?? null;
            $author = null;
            $description = $wrapper->descriptionSnippet->runs[0]->text ?? null;
            // 5 days ago | 1 month ago
            $publishedTimeText = $wrapper->publishedTimeText->simpleText ?? $wrapper->videoInfo->runs[2]->text ?? null;
            $timestamp = null;
            if ($publishedTimeText) {
                try {
                    $publicationDate = new \DateTimeImmutable($publishedTimeText);
                    // Hard-code hour, minute and second
                    $publicationDate = $publicationDate->setTime(0, 0, 0);
                    $timestamp = $publicationDate->getTimestamp();
                } catch (\Exception $e) {
                }
            }

            $durationText = 0;
            if ($lengthText) {
                $durationText = $lengthText;
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
                if ($duration < $minimumDurationSeconds || $duration > $maximumDurationSeconds) {
                    continue;
                }
            }
            if (!$description || !$timestamp) {
                $this->fetchVideoDetails($videoId, $author, $description, $timestamp);
            }
            $this->addItem($videoId, $title, $author, $description, $timestamp);
            if (count($this->items) >= 99) {
                break;
            }
        }
    }

    private function addItem($videoId, $title, $author, $description, $timestamp, $thumbnail = '')
    {
        $description = nl2br($description);

        $item = [];
        // This should probably be uid?
        $item['id'] = $videoId;
        $item['title'] = $title;
        $item['author'] = $author ?? '';
        $item['timestamp'] = $timestamp;
        $item['uri'] = self::URI . '/watch?v=' . $videoId;
        if (!$thumbnail) {
            // Fallback to default thumbnail if there aren't any provided.
            $thumbnail = '0';
        }
        $thumbnailUri = str_replace('/www.', '/img.', self::URI) . '/vi/' . $videoId . '/' . $thumbnail . '.jpg';
        $item['content'] = sprintf('<a href="%s"><img src="%s" /></a><br />%s', $item['uri'], $thumbnailUri, $description);
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
