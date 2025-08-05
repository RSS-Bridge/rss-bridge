<?php

class YouTubeCommunityTabBridge extends BridgeAbstract
{
    const NAME = 'YouTube Posts Tab Bridge';
    const URI = 'https://www.youtube.com';
    const DESCRIPTION = 'Returns posts from a channel\'s posts tab';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'By channel ID' => [
            'channel' => [
                'name' => 'Channel ID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'UCULkRHBdLC5ZcEQBaL0oYHQ'
            ]
        ],
        'By username' => [
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'YouTubeUK'
            ],
        ]
    ];

    const CACHE_TIMEOUT = 3600; // 1 hour

    private $feedUrl = '';
    private $feedName = '';
    private $itemTitle = '';

    private $urlRegex = '/youtube\.com\/(channel|user|c)\/([\w]+)\/posts/';
    private $jsonRegex = '/var ytInitialData = ([^<]*);<\/script>/';

    public function detectParameters($url)
    {
        $params = [];

        if (preg_match($this->urlRegex, $url, $matches)) {
            if ($matches[1] === 'channel') {
                $params['context'] = 'By channel ID';
                $params['channel'] = $matches[2];
            }

            if ($matches[1] === 'user') {
                $params['context'] = 'By username';
                $params['username'] = $matches[2];
            }

            return $params;
        }

        return null;
    }

    public function collectData()
    {
        if (is_null($this->getInput('username')) === false) {
            try {
                $this->feedUrl = $this->buildPostsUri($this->getInput('username'), 'c');
                $html = getSimpleHTMLDOM($this->feedUrl);
            } catch (Exception $e) {
                $this->feedUrl = $this->buildPostsUri($this->getInput('username'), 'user');
                $html = getSimpleHTMLDOM($this->feedUrl);
            }
        } else {
            $this->feedUrl = $this->buildPostsUri($this->getInput('channel'), 'channel');
            $html = getSimpleHTMLDOM($this->feedUrl);
        }

        $json = $this->extractJson($html->find('html', 0)->innertext);

        $this->feedName = $json->header->c4TabbedHeaderRenderer->title ?? null;
        $this->feedName ??= $json->header->pageHeaderRenderer->pageTitle ?? null;
        $this->feedName ??= $json->metadata->channelMetadataRenderer->title ?? null;
        $this->feedName ??= $json->microformat->microformatDataRenderer->title ?? null;
        $this->feedName ??= '';

        if ($this->hasPostsTab($json) === false) {
            throwServerException('Channel does not have a posts tab');
        }

        $posts = $this->getPosts($json);
        foreach ($posts as $key => $post) {
            $this->itemTitle = '';

            if (!isset($post->backstagePostThreadRenderer)) {
                continue;
            }

            if (isset($post->backstagePostThreadRenderer->post->backstagePostRenderer)) {
                $details = $post->backstagePostThreadRenderer->post->backstagePostRenderer;
            } elseif (isset($post->backstagePostThreadRenderer->post->sharedPostRenderer)) {
                // todo: properly extract data from this shared post
                $details = $post->backstagePostThreadRenderer->post->sharedPostRenderer;
            } else {
                continue;
            }

            $item = [];
            $item['uri'] = self::URI . '/post/' . $details->postId;
            $item['author'] = $details->authorText->runs[0]->text ?? null;
            $item['content'] = $item['uri'];

            if (isset($details->contentText->runs)) {
                $text = $this->getText($details->contentText->runs);

                $this->itemTitle = $this->ellipsisTitle($text);
                $item['content'] = $text;
            }

            $item['content'] .= $this->getAttachments($details);
            $item['title'] = $this->itemTitle;

            $date = strtotime(str_replace(' (edited)', '', $details->publishedTimeText->runs[0]->text));
            if (is_int($date)) {
                // subtract an increasing multiple of 60 seconds to always preserve the original order
                $item['timestamp'] = $date - $key * 60;
            }

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!empty($this->feedUrl)) {
            return $this->feedUrl;
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - YouTube Posts Tab';
        }

        return parent::getName();
    }

    /**
     * Build Posts URI
     */
    private function buildPostsUri($value, $type)
    {
        return self::URI . '/' . $type . '/' . $value . '/posts';
    }

    /**
     * Extract JSON from page
     */
    private function extractJson($html)
    {
        if (!preg_match($this->jsonRegex, $html, $parts)) {
            throwServerException('Failed to extract data from page');
        }

        $data = json_decode($parts[1]);

        if ($data === false) {
            throwServerException('Failed to decode extracted data');
        }

        return $data;
    }

    /**
     * Check if channel has a posts tab
     */
    private function hasPostsTab($json)
    {
        foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
            if (
                isset($tab->tabRenderer)
                && str_ends_with($tab->tabRenderer->endpoint->commandMetadata->webCommandMetadata->url, 'posts')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get posts from posts tab
     */
    private function getPosts($json)
    {
        foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
            if (
                isset($tab->tabRenderer)
                && str_ends_with($tab->tabRenderer->endpoint->commandMetadata->webCommandMetadata->url, 'posts')
            ) {
                return $tab->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer->contents;
            }
        }
    }

    /**
     * Get text content for a post
     */
    private function getText($runs)
    {
        $text = '';

        foreach ($runs as $part) {
            if (isset($part->navigationEndpoint->browseEndpoint->canonicalBaseUrl)) {
                $text .= $this->formatUrls($part->text, $part->navigationEndpoint->browseEndpoint->canonicalBaseUrl);
            } elseif (isset($part->navigationEndpoint->urlEndpoint->url)) {
                $text .= $this->formatUrls($part->text, $part->navigationEndpoint->urlEndpoint->url);
            } elseif (isset($part->navigationEndpoint->commandMetadata->webCommandMetadata->url)) {
                $text .= $this->formatUrls($part->text, $part->navigationEndpoint->commandMetadata->webCommandMetadata->url);
            } else {
                $text .= $this->formatUrls($part->text, null);
            }
        }

        return nl2br($text);
    }

    /**
     * Get attachments for posts
     */
    private function getAttachments($details)
    {
        $content = '';

        if (isset($details->backstageAttachment)) {
            $attachments = $details->backstageAttachment;

            if (isset($attachments->videoRenderer) && isset($attachments->videoRenderer->videoId)) {
                // Video
                if (empty($this->itemTitle)) {
                    $this->itemTitle = $this->feedName . ' posted a video';
                }

                $content = <<<EOD
<iframe width="100%" height="410" src="https://www.youtube.com/embed/{$attachments->videoRenderer->videoId}" 
frameborder="0" allow="encrypted-media;" allowfullscreen></iframe>
EOD;
            } elseif (isset($attachments->backstageImageRenderer)) {
                // Image
                if (empty($this->itemTitle)) {
                    $this->itemTitle = $this->feedName . ' posted an image';
                }

                $lastThumb = end($attachments->backstageImageRenderer->image->thumbnails);

                $content = <<<EOD
<p><img src="{$lastThumb->url}"></p>
EOD;
            } elseif (isset($attachments->pollRenderer)) {
                // Poll
                if (empty($this->itemTitle)) {
                    $this->itemTitle = $this->feedName . ' posted a poll';
                }

                $pollChoices = '';

                foreach ($attachments->pollRenderer->choices as $choice) {
                    $pollChoices .= <<<EOD
<li>{$choice->text->runs[0]->text}</li>
EOD;
                }

                $content = <<<EOD
<hr><p>Poll ({$attachments->pollRenderer->totalVotes->simpleText})<br><ul>{$pollChoices}</ul><p>
EOD;
            } elseif (isset($attachments->postMultiImageRenderer->images)) {
                // Multiple images
                $images = $attachments->postMultiImageRenderer->images;

                if (is_array($images)) {
                    if (empty($this->itemTitle)) {
                        $this->itemTitle = $this->feedName . ' posted ' . count($images) . ' images';
                    }

                    foreach ($images as $image) {
                        $lastThumb = end($image->backstageImageRenderer->image->thumbnails);

                        $content .= <<<EOD
<p><img src="{$lastThumb->url}"></p>
EOD;
                    }
                }
            }
        }

        return $content;
    }

    /*
        Ellipsis text for title
    */
    private function ellipsisTitle($text)
    {
        $length = 100;

        $text = strip_tags($text);
        if (strlen($text) > $length) {
            $text = explode('<br>', wordwrap($text, $length, '<br>'));
            return $text[0] . '...';
        }

        return $text;
    }

    private function formatUrls($content, $url)
    {
        if (substr(strval($url), 0, 1) == '/') {
            // fix relative URL
            $url = 'https://www.youtube.com' . $url;
        } elseif (substr(strval($url), 0, 33) == 'https://www.youtube.com/redirect?') {
            // extract actual URL from YouTube redirect
            parse_str(substr($url, 33), $params);
            if (strpos(($params['q'] ?? ''), rtrim($content, '.')) === 0) {
                $url = $params['q'];
            }
        }

        // ensure all URLs are made clickable
        $url = $url ?? $content;

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<a href="' . $url . '" target="_blank">' . $content . '</a>';
        }

        return $content;
    }
}
