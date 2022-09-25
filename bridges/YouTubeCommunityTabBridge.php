<?php

class YouTubeCommunityTabBridge extends BridgeAbstract
{
    const NAME = 'YouTube Community Tab Bridge';
    const URI = 'https://www.youtube.com';
    const DESCRIPTION = 'Returns posts from a channel\'s community tab';
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

    private $urlRegex = '/youtube\.com\/(channel|user|c)\/([\w]+)\/community/';
    private $jsonRegex = '/var ytInitialData = (.*);<\/script>/';

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
                $this->feedUrl = $this->buildCommunityUri($this->getInput('username'), 'c');
                $html = getSimpleHTMLDOM($this->feedUrl);
            } catch (Exception $e) {
                $this->feedUrl = $this->buildCommunityUri($this->getInput('username'), 'user');
                $html = getSimpleHTMLDOM($this->feedUrl);
            }
        } else {
            $this->feedUrl = $this->buildCommunityUri($this->getInput('channel'), 'channel');
            $html = getSimpleHTMLDOM($this->feedUrl);
        }

        $json = $this->extractJson($html->find('body', 0)->innertext);

        $this->feedName = $json->header->c4TabbedHeaderRenderer->title;

        if ($this->hasCommunityTab($json) === false) {
            returnServerError('Channel does not have a community tab');
        }

        foreach ($this->getCommunityPosts($json) as $post) {
            $this->itemTitle = '';

            if (!isset($post->backstagePostThreadRenderer)) {
                continue;
            }

            $details = $post->backstagePostThreadRenderer->post->backstagePostRenderer;

            $item = [];
            $item['uri'] = self::URI . '/post/' . $details->postId;
            $item['author'] = $details->authorText->runs[0]->text;
            $item['content'] = '';

            if (isset($details->contentText)) {
                $text = $this->getText($details->contentText->runs);

                $this->itemTitle = $this->ellipsisTitle($text);
                $item['content'] = $text;
            }

            $item['content'] .= $this->getAttachments($details);
            $item['title'] = $this->itemTitle;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!empty($this->feedUri)) {
            return $this->feedUri;
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - YouTube Community Tab';
        }

        return parent::getName();
    }

    /**
     * Build Community URI
     */
    private function buildCommunityUri($value, $type)
    {
        return self::URI . '/' . $type . '/' . $value . '/community';
    }

    /**
     * Extract JSON from page
     */
    private function extractJson($html)
    {
        if (!preg_match($this->jsonRegex, $html, $parts)) {
            returnServerError('Failed to extract data from page');
        }

        $data = json_decode($parts[1]);

        if ($data === false) {
            returnServerError('Failed to decode extracted data');
        }

        return $data;
    }

    /**
     * Check if channel has a community tab
     */
    private function hasCommunityTab($json)
    {
        foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
            if (
                isset($tab->tabRenderer)
                && str_ends_with($tab->tabRenderer->endpoint->commandMetadata->webCommandMetadata->url, 'community')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get community tab posts
     */
    private function getCommunityPosts($json)
    {
        foreach ($json->contents->twoColumnBrowseResultsRenderer->tabs as $tab) {
            if (
                isset($tab->tabRenderer)
                && str_ends_with($tab->tabRenderer->endpoint->commandMetadata->webCommandMetadata->url, 'community')
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
            $text .= $this->formatUrls($part->text);
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

            // Video
            if (isset($attachments->videoRenderer) && isset($attachments->videoRenderer->videoId)) {
                if (empty($this->itemTitle)) {
                    $this->itemTitle = $this->feedName . ' posted a video';
                }

                $content = <<<EOD
<iframe width="100%" height="410" src="https://www.youtube.com/embed/{$attachments->videoRenderer->videoId}" 
frameborder="0" allow="encrypted-media;" allowfullscreen></iframe>
EOD;
            }

            // Image
            if (isset($attachments->backstageImageRenderer)) {
                if (empty($this->itemTitle)) {
                    $this->itemTitle = $this->feedName . ' posted an image';
                }

                $lastThumb = end($attachments->backstageImageRenderer->image->thumbnails);

                $content = <<<EOD
<p><img src="{$lastThumb->url}"></p>
EOD;
            }

            // Poll
            if (isset($attachments->pollRenderer)) {
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

        if (strlen($text) > $length) {
            $text = explode('<br>', wordwrap($text, $length, '<br>'));
            return $text[0] . '...';
        }

        return $text;
    }

    private function formatUrls($content)
    {
        return preg_replace(
            '/(http[s]{0,1}\:\/\/[a-zA-Z0-9.\/\?\&=\-_]{4,})/ims',
            '<a target="_blank" href="$1" target="_blank">$1</a> ',
            $content
        );
    }
}
