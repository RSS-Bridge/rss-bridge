<?php
/**
* RssBridgeEmbedYoutube
* Get YouTube videos as embeds in RSS items for easy viewing.
*
* WARNING: to parse big playlists (over ~90 videos), you need to edit simple_html_dom.php:
* change: define('MAX_FILE_SIZE', 600000);
* into:   define('MAX_FILE_SIZE', 900000);  (or more)
*/
class YoutubeEmbedBridge extends BridgeAbstract
{
    const NAME = 'YouTube Embed Bridge';
    const MAINTAINER = 'Arnan de Gans';
    const URI = 'https://www.youtube.com';
    const CACHE_TIMEOUT = 60 * 60 * 3;
    const DESCRIPTION = 'Get the newest videos from a channel or playlist embedded in your RSS items.';

    const PARAMETERS = [
        'By channel handle' => [
            'custom' => [
                'name' => 'Channel Handle',
                'exampleValue' => '@youtube',
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
        'By playlist Id' => [
            'p' => [
                'name' => 'playlist id',
                'exampleValue' => 'PL8mG-RkN2uTzJc8N0EoyhdC54prvBBLpj',
                'required' => true
            ]
        ]
    ];

    private $feedname = '';
    private $feedurl = '';
    private $feediconurl = '';

    // This took from repo BetterVideoRss of VerifiedJoseph.
    const URI_REGEX = '/(https?:\/\/(?:www\.)?(?:[a-zA-Z0-9-.]{2,256}\.[a-z]{2,20})(\:[0-9]{2,4})?(?:\/[a-zA-Z0-9@:%_\+.,~#"\'!?&\/\/=\-*]+|\/)?)/ims'; //phpcs:ignore

    public function collectData()
    {
        $cachekey = 'youtube_rate_limit';
        if ($this->cache->get($cachekey)) {
            throw new HttpException('429 Too Many Requests', 429);
        }
        try {
            $this->collectDataInternal();
        } catch (HttpException $e) {
            if ($e->getCode() === 429) {
                $this->cache->set($cachekey, true, 60 * 16);
            }
            throw $e;
        }
    }

    private function collectDataInternal()
    {
        $html = '';
        $url_feed = '';
        $url_listing = '';

        $channel_handle = $this->getInput('custom');
        $channel_id = $this->getInput('c');
        $playlist_id = $this->getInput('p');

        if ($channel_handle) {
			$channel_handle = urlencode($channel_handle);
            $url_listing = self::URI . '/' . $channel_handle . '/videos';
        } elseif ($channel_id) {
			$channel_id = urlencode($channel_id);
            $url_feed = self::URI . '/feeds/videos.xml?channel_id=' . $channel_id;
            $url_listing = self::URI . '/channel/' . $channel_id . '/videos';
        }

        if ($url_feed || $url_listing) {
            // channel or handle
            $this->feedurl = $url_listing;
            if ($channel_handle) {
                // Extract the feed url for the channel handle
                $html = $this->fetch($url_listing);
                $jsonData = $this->extractJsonFromHtml($html);

                // Pluck out the rss feed url
                $url_feed = $jsonData->metadata->channelmetadatarenderer->rssurl;
                $this->feediconurl = $jsonData->metadata->channelmetadatarenderer->avatar->thumbnails[0]->url;
            }

            // Fetch the xml feed
            $html = $this->fetch($url_feed);
            $this->extractItemsFromXmlFeed($html);

            $this->feedname = str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
        } elseif ($playlist_id) {
			$playlist_id = urlencode($playlist_id);
            $url_feed = self::URI . '/feeds/videos.xml?playlist_id=' . $playlist_id;
            $url_listing = self::URI . '/playlist?list=' . $playlist_id;

            $html = $this->fetch($url_listing);
            $jsondata = $this->extractJsonFromHtml($html);

            // TODO: this method returns only first 100 video items
            // if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
            $jsondata = $jsondata->contents->twoColumnBrowseResultsRenderer->tabs[0] ?? null;

            if (!$jsondata) {
                // playlist probably doesnt exists
                throw new \Exception('Unable to find playlist: ' . $url_listing);
            }

            $jsondata = $jsondata->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
            $jsondata = $jsondata->contents[0]->playlistVideoListRenderer->contents;
            $item_count = count($jsondata);

            if ($item_count > 15) {
                $this->fetchItemsFromFromJsonData($jsondata);
            } else {
                $xml = $this->fetch($url_feed);
                $this->extractItemsFromXmlFeed($xml);
            }

            $this->feedname = 'Playlist: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
            usort($this->items, function ($item1, $item2) {
                if (!is_int($item1['timestamp']) && !is_int($item2['timestamp'])) {
                    $item1['timestamp'] = strtotime($item1['timestamp']);
                    $item2['timestamp'] = strtotime($item2['timestamp']);
                }
                return $item2['timestamp'] - $item1['timestamp'];
            });
        } else {
            returnClientError("You must either specify either:\n - Channel id (?c=...)\n - Playlist id (?p=...)");
        }
    }

    private function fetchVideoDetails($videoid, &$author, &$description, &$timestamp)
    {
        $url = self::URI . "/watch?v=$videoid";
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

        $jsondata = $this->extractJsonFromHtml($html);
        if (!isset($jsondata->contents)) {
            return;
        }

        $jsondata = $jsondata->contents->twoColumnWatchNextResults->results->results->contents ?? null;
        if (!$jsondata) {
            throw new \Exception('Unable to find json data');
        }
        $videoSecondaryInfo = null;
        foreach ($jsondata as $item) {
            if (isset($item->videoSecondaryInfoRenderer)) {
                $videoSecondaryInfo = $item->videoSecondaryInfoRenderer;
                break;
            }
        }
        if (!$videoSecondaryInfo) {
            returnServerError('Could not find videoSecondaryInfoRenderer. Error at: ' . $videoid);
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
        $this->feedname = $this->decodeTitle($xml->find('feed > title', 0)->plaintext);

        foreach ($xml->find('entry') as $element) {
            $videoid = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
            if (strpos($videoid, 'googleads') !== false) {
                continue;
            }
            $title = $this->decodeTitle($element->find('title', 0)->plaintext);
            $author = $element->find('name', 0)->plaintext;
            $desc = $element->find('media:description', 0)->innertext;
            $desc = nl2br(htmlspecialchars($desc));
            $desc = preg_replace(self::URI_REGEX, '<a href="$1" target="_blank">$1</a> ', $desc);
            $time = strtotime($element->find('published', 0)->plaintext);
            $this->addItem($videoid, $title, $author, $desc, $time);
        }
    }

    private function fetch($url, bool $cache = false)
    {
        $header = ['Accept-Language: en-US'];
        if ($cache) {
            return getSimpleHTMLDOMCached($url, 259200, $header, [], true, true, DEFAULT_TARGET_CHARSET, false);
        }
        return getSimpleHTMLDOM($url, $header, [], true, true, DEFAULT_TARGET_CHARSET, false);
    }

    private function extractJsonFromHtml($html)
    {
        $result = preg_match('/var ytInitialData = (.*?);<\/script>/', $html, $matches);
        if (! $result) {
            $this->logger->debug('Could not find ytInitialData');
            return null;
        }
        $data = json_decode($matches[1]);
        return $data;
    }

    private function fetchItemsFromFromJsonData($jsondata)
    {
        foreach ($jsondata as $item) {
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

            // 6,875 views
            $viewCount = $wrapper->viewCountText->simpleText ?? null;
            // Dc645M8Het8
            $videoid = $wrapper->videoId;
            // Jumbo frames - transfer more data faster!
            $title = $wrapper->title->runs[0]->text ?? $wrapper->title->accessibility->accessibilityData->label ?? null;
            $author = null;
            $description = $wrapper->descriptionSnippet->runs[0]->text ?? null;
            // 5 days ago | 1 month ago
            $publishedtimetext = $wrapper->publishedTimeText->simpleText ?? $wrapper->videoInfo->runs[2]->text ?? null;
            $timestamp = null;
            if ($publishedtimetext) {
                try {
                    $publicationdate = new \DateTimeImmutable($publishedtimetext);
                    // Hard-code hour, minute and second
                    $publicationdate = $publicationdate->setTime(0, 0, 0);
                    $timestamp = $publicationdate->getTimestamp();
                } catch (\Exception $e) {
                }
            }

            if (!$description || !$timestamp) {
                $this->fetchVideoDetails($videoid, $author, $description, $timestamp);
            }

            $this->addItem($videoid, $title, $author, $description, $timestamp);

            if (count($this->items) >= 99) {
                break;
            }
        }
    }

    private function addItem($videoid, $title, $author, $description, $timestamp)
    {
        $description = nl2br($description);
        $embedurl = self::URI.'/embed/'.$videoid;

        $item = [];
        // This should probably be uid?
        $item['id'] = $videoid;
        $item['title'] = $title;
        $item['author'] = $author ?? '';
        $item['timestamp'] = $timestamp;
        $item['uri'] = self::URI . '/watch?v=' . $videoid;
		// Add full width video in 16:9 aspect
		$item['content'] = '
			<div style="position:relative; padding-bottom:56.25%; padding-top:15px; height:0;">
				<embed src="'.$embedurl.'" style="position:absolute; top:0; left:0; width:100%; height:100%; max-height:540px;" width="720" height="540" />
			</div>
			<a href="'.$item['uri'].'">Watch video on YouTube</a>
			<br /><br />'.$description;

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
        } elseif ($this->feedurl) {
            return $this->feedurl;
        }

        return parent::getURI();
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By channel handle':
			case 'By channel id':
            case 'By playlist Id':
                return htmlspecialchars_decode($this->feedname) . ' - YouTube';
            default:
                return parent::getName();
        }
    }

    public function getIcon()
    {
        if (empty($this->feediconurl)) {
            return parent::getIcon();
        } else {
            return $this->feediconurl;
        }
    }
}