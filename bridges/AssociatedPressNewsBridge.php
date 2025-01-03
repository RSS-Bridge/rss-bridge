<?php

class AssociatedPressNewsBridge extends BridgeAbstract
{
    const NAME = 'Associated Press News Bridge';
    const URI = 'https://apnews.com/';
    const DESCRIPTION = 'Returns newest articles by topic';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'Standard Topics' => [
            'topic' => [
                'name' => 'Topic',
                'type' => 'list',
                'values' => [
                    'AP Top News' => 'apf-topnews',
                    'Sports' => 'apf-sports',
                    'Entertainment' => 'apf-entertainment',
                    'Oddities' => 'apf-oddities',
                    'Travel' => 'apf-Travel',
                    'Technology' => 'apf-technology',
                    'Lifestyle' => 'apf-lifestyle',
                    'Business' => 'apf-business',
                    'U.S. News' => 'apf-usnews',
                    'Health' => 'apf-Health',
                    'Science' => 'apf-science',
                    'World News' => 'apf-WorldNews',
                    'Politics' => 'apf-politics',
                    'Religion' => 'apf-religion',
                    'Photo Galleries' => 'PhotoGalleries',
                    'Fact Checks' => 'APFactCheck',
                    'Videos' => 'apf-videos',
                ],
                'defaultValue' => 'apf-topnews',
            ],
        ],
        'Custom Topic' => [
            'topic' => [
                'name' => 'Topic',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'europe'
            ],
        ]
    ];

    const CACHE_TIMEOUT = 900; // 15 mins

    private $detectParamRegex = '/^https?:\/\/(?:www\.)?apnews\.com\/(?:[tag|hub]+\/)?([\w-]+)$/';
    private $tagEndpoint = 'https://afs-prod.appspot.com/api/v2/feed/tag?tags=';
    private $feedName = '';

    public function detectParameters($url)
    {
        $params = [];

        if (preg_match($this->detectParamRegex, $url, $matches) > 0) {
            $params['topic'] = $matches[1];
            $params['context'] = 'Custom Topic';
            return $params;
        }

        return null;
    }

    public function collectData()
    {
        switch ($this->getInput('topic')) {
            case 'Podcasts':
                returnClientError('Podcasts topic feed is not supported');
                break;
            case 'PressReleases':
                returnClientError('PressReleases topic feed is not supported');
                break;
            default:
                $this->collectCardData();
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('topic'))) {
            return self::URI . $this->getInput('topic');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - Associated Press';
        }

        return parent::getName();
    }

    private function getTagURI()
    {
        if (!is_null($this->getInput('topic'))) {
            return $this->tagEndpoint . $this->getInput('topic');
        }

        return parent::getURI();
    }

    private function collectCardData()
    {
        $json = getContents($this->getTagURI());

        $tagContents = json_decode($json, true);

        if (empty($tagContents['tagObjs'])) {
            returnClientError('Topic not found: ' . $this->getInput('topic'));
        }

        $this->feedName = $tagContents['tagObjs'][0]['name'];

        foreach ($tagContents['cards'] as $card) {
            $item = [];

            // skip hub peeks & Notifications
            if ($card['cardType'] == 'Hub Peek' || $card['cardType'] == 'Notification') {
                continue;
            }

            $storyContent = $card['contents'][0];

            switch ($storyContent['contentType']) {
                case 'web': // Skip link only content
                    continue 2;

                case 'video':
                    $html = $this->processVideo($storyContent);

                    $item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/'
                        . $storyContent['media'][0]['id'] . '/800.jpeg';
                    break;
                default:
                    if (empty($storyContent['storyHTML'])) { // Skip if no storyHTML
                        continue 2;
                    }

                    $html = defaultLinkTo($storyContent['storyHTML'], self::URI);
                    $html = str_get_html($html);

                    $this->processMediaPlaceholders($html, $storyContent['id']);
                    $this->processHubLinks($html, $storyContent);
                    $this->processIframes($html);

                    if (!is_null($storyContent['leadPhotoId'])) {
                        $leadPhotoUrl = sprintf('https://storage.googleapis.com/afs-prod/media/%s/800.jpeg', $storyContent['leadPhotoId']);
                        $leadPhotoImageTag = sprintf('<img src="%s">', $leadPhotoUrl);
                        // Move the image to the beginning of the content
                        $html = $leadPhotoImageTag . $html;
                        // Explicitly not adding it to the item's enclosures!
                    }
            }

            $item['title'] = $card['contents'][0]['headline'];
            $item['uri'] = self::URI . $card['shortId'];

            if ($card['contents'][0]['localLinkUrl']) {
                $item['uri'] = $card['contents'][0]['localLinkUrl'];
            }

            $item['timestamp'] = $storyContent['published'];

            if (is_null($storyContent['bylines']) === false) {
                // Remove 'By' from the bylines
                if (substr($storyContent['bylines'], 0, 2) == 'By') {
                    $item['author'] = ltrim($storyContent['bylines'], 'By ');
                } else {
                    $item['author'] = $storyContent['bylines'];
                }
            }

            $item['content'] = $html;

            foreach ($storyContent['tagObjs'] as $tag) {
                $item['categories'][] = $tag['name'];
            }

            $this->items[] = $item;

            if (count($this->items) >= 15) {
                break;
            }
        }
    }

    private function processMediaPlaceholders($html, $id)
    {
        if ($html->find('div.media-placeholder', 0)) {
            // Fetch page content
            $json = getContents('https://afs-prod.appspot.com/api/v2/content/' . $id);
            $storyContent = json_decode($json, true);

            foreach ($html->find('div.media-placeholder') as $div) {
                $key = array_search($div->id, $storyContent['mediumIds']);

                if (!isset($storyContent['media'][$key])) {
                    continue;
                }

                $media = $storyContent['media'][$key];

                if ($media['type'] === 'Photo') {
                    $mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
                    $mediaCaption = $media['caption'];

                    $div->outertext = <<<EOD
	<figure><img loading="lazy" src="{$mediaUrl}"/><figcaption>{$mediaCaption}</figcaption></figure>
EOD;
                }

                if ($media['type'] === 'YouTube') {
                    $div->outertext = <<<EOD
	<iframe src="https://www.youtube.com/embed/{$media['externalId']}" width="560" height="315">
	</iframe>
EOD;
                }
            }
        }
    }

    /*
        Create full coverage links (HubLinks)
    */
    private function processHubLinks($html, $storyContent)
    {
        if (!empty($storyContent['richEmbeds'])) {
            foreach ($storyContent['richEmbeds'] as $embed) {
                if ($embed['type'] === 'Hub Link') {
                    $url = self::URI . $embed['tag']['id'];
                    $div = $html->find('div[id=' . $embed['id'] . ']', 0);

                    if ($div) {
                        $div->outertext = <<<EOD
<p><a href="{$url}">{$embed['calloutText']} {$embed['displayName']}</a></p>
EOD;
                    }
                }
            }
        }
    }

    private function processVideo($storyContent)
    {
        $video = $storyContent['media'][0];

        if ($video['type'] === 'YouTube') {
            $url = 'https://www.youtube.com/embed/' . $video['externalId'];
            $html = <<<EOD
<iframe width="560" height="315" src="{$url}" frameborder="0" allowfullscreen></iframe>
EOD;
        } else {
            $html = <<<EOD
<video controls poster="https://storage.googleapis.com/afs-prod/media/{$video['id']}/800.jpeg" preload="none">
	<source src="{$video['gcsBaseUrl']} {$video['videoRenderedSizes'][0]} {$video['videoFileExtension']}" type="video/mp4">
</video>
EOD;
        }

        return $html;
    }

    // Remove datawrapper.dwcdn.net iframes and related javaScript
    private function processIframes($html)
    {
        foreach ($html->find('iframe') as $index => $iframe) {
            if (preg_match('/datawrapper\.dwcdn\.net/', $iframe->src)) {
                $iframe->outertext = '';

                if ($html->find('script', $index)) {
                    $html->find('script', $index)->outertext = '';
                }
            }
        }
    }
}
