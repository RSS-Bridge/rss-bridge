<?php

class NationalGeographicBridge extends BridgeAbstract
{
    const CONTEXT_BY_TOPIC = 'By Topic';
    const PARAMETER_TOPIC = 'topic';
    const PARAMETER_FULL_ARTICLE = 'full';
    const TOPIC_MAGAZINE = 'Magazine';
    const TOPIC_LATEST_STORIES = 'Latest Stories';
    const CACHE_TIMEOUT = 900; //15 min

    const NAME = 'National Geographic';
    const URI = 'https://www.nationalgeographic.com/';
    const DESCRIPTION = 'Fetches the latest articles from the National Geographic Magazine';
    const MAINTAINER = 'csisoap';
    const PARAMETERS = [
        self::CONTEXT_BY_TOPIC => [
            self::PARAMETER_TOPIC => [
                'name' => 'Topic',
                'type' => 'list',
                'values' => [
                    self::TOPIC_MAGAZINE => 'magazine',
                    self::TOPIC_LATEST_STORIES => 'latest-stories'
                ],
                'title' => 'Select your topic',
                'defaultValue' => 'Magazine'
            ]
        ],
        'global' => [
            self::PARAMETER_FULL_ARTICLE => [
                'name' => 'Full Article',
                'type' => 'checkbox',
                'title' => 'Enable to load full articles and other infos (takes longer)'
            ]
        ]
    ];

    private $topicName = '';
    const CONTEXT = 'eyJjb250ZW50VHlwZSI6IlVuaXNvbkh1YiIsInZhcmlhYmxlcyI6eyJsb2NhdG9yIjoiL3BhZ2VzL3
			RvcGljL2xhdGVzdC1zdG9yaWVzIiwicG9ydGZvbGlvIjoibmF0Z2VvIiwicXVlcn
			lUeXBlIjoiTE9DQVRPUiJ9LCJtb2R1bGVJZCI6bnVsbH0';
    const LATEST_STORIES_ID = [
        '1df278bb-0e3d-4a67-a0ce-8fae48392822-f2-m1'
    ];
    const MAGAZINE_ID = [
        '94d87d74-f41a-4a32-9acd-b591ba2df288-f2-m1',
        '94d87d74-f41a-4a32-9acd-b591ba2df288-f5-m2',
    ];

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_BY_TOPIC:
                return self::URI . $this->getInput(self::PARAMETER_TOPIC);
            default:
                return parent::getURI();
        }
    }

    private function getAPIURL($id)
    {
        $context = preg_replace('/\s*/m', '', self::CONTEXT);
        $url = 'https://www.nationalgeographic.com/proxy/hub?context='
                        . $context . '&id=' . $id
                        . '&moduleType=InfiniteFeedModule&_xhr=pageContent';
        return $url;
    }

    public function collectData()
    {
        $this->topicName = $this->getTopicName($this->getInput(self::PARAMETER_TOPIC));
        switch ($this->topicName) {
            case self::TOPIC_MAGAZINE:
                return $this->collectMagazine();
            case self::TOPIC_LATEST_STORIES:
                return $this->collectLatestStories();
            default:
                returnServerError('Unknown topic: "' . $this->topicName . '"');
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_BY_TOPIC:
                return static::NAME . ': ' . $this->topicName;
            default:
                return parent::getName();
        }
    }

    private function getTopicName($topic)
    {
        return array_search($topic, static::PARAMETERS[self::CONTEXT_BY_TOPIC][self::PARAMETER_TOPIC]['values']);
    }

    private function collectMagazine()
    {
        $stories = [];

        foreach (self::MAGAZINE_ID as $id) {
            $uri = $this->getAPIURL($id);

            $json_raw = getContents($uri);

            $json = json_decode($json_raw, true)['tiles'];
            $stories = array_merge($json, $stories);
        }

        foreach ($stories as $story) {
            $this->addStory($story);
        }
    }

    private function collectLatestStories()
    {
        $stories = [];

        foreach (self::LATEST_STORIES_ID as $id) {
            $uri = $this->getAPIURL($id);

            $json_raw = getContents($uri);

            $json = json_decode($json_raw, true)['tiles'];
            $stories = array_merge($stories, $json);
        }

        foreach ($stories as $story) {
            $this->addStory($story);
        }
    }

    private function addStory($story)
    {
        $title = 'Unknown title';
        $content = '';
        $story_type = '';
        $uri = '';

        foreach ($story['ctas'] as $component) {
            $uri = $component['url'];
            $story_type = $component['icon'];
        }

        $item = [];
        if (isset($story['description'])) {
            $content = '<p>' . $story['description'] . '</p>';
        }
        $title = $story['title'];
        $item['uri'] = $uri;
        $item['title'] = $story['title'];

        // if full article is requested!
        if ($this->getInput(self::PARAMETER_FULL_ARTICLE)) {
            if ($story_type != 'interactive') {
                /* Nat Geo doesn't provided much info about interactive page
                *       and it requires JS to load the interactive.
                */
                $article_data = $this->getFullArticle($item['uri']);
                $item['timestamp'] = $article_data['published_date'];
                $item['author'] = $article_data['authors'];
                $item['content'] = $content . $article_data['content'];
            } else {
                $item['content'] = $content;
            }
        } else {
            $item['content'] = $content;
        }

        $image = $story['img'];
        $item['enclosures'][] = str_replace(' ', '%20', $image['src']);

        foreach ($story['tags'] as $tag) {
            $item['categories'][] = $tag['name'] ?? $tag;
        }

        $this->items[] = $item;
    }

    private function filterArticleData($data)
    {
        $article_module = array_filter(
            $data,
            function ($item) {
                if (isset($item['id']) && $item['id'] == 'natgeo-template1-frame-1') {
                    return true;
                }
            }
        );

        $article_data = array_reduce(
            $article_module,
            function (array $carry, array $item) {
                $module = $item['mods'];
                return array_merge(
                    $carry,
                    array_filter(
                        $module,
                        function ($data) {
                            return $data['id'] == 'natgeo-template1-frame-1-module-1';
                        }
                    )
                );
            },
            []
        );

        return $article_data[0];
    }

    private function handleImages($image_module, $image_type)
    {
        $image_alt = '';
        $image_credit = '';
        $image_src = '';
        $image_caption = '';
        $caption = '';
        switch ($image_type) {
            case 'image':
            case 'imagegroup':
                $image = $image_module['image'] ?? null;
                if (!$image) {
                    return '';
                }
                $image_src = $image['src'];
                if (isset($image_module['alt'])) {
                    $image_alt = $image_module['alt'];
                } elseif (isset($image['altText'])) {
                    $image_alt = $image['altText'];
                }
                if (isset($image['crdt'])) {
                    $image_credit = $image['crdt'];
                }
                $caption = ($image_module['caption'] ?? '');
                break;
            case 'photogallery':
                $image_credit = ($image_module['caption']['credit'] ?? '');
                $caption = $image_module['caption']['text'];
                $image_src = $image_module['img']['src'];
                $image_alt = $image_module['img']['altText'];
                break;
            case 'video':
                $image_credit = ($image_module['credit'] ?? '');
                $description = ($image_module['description'] ?? '');
                $caption = $description . ' Video can be watched on the article\'s page';
                $image = $image_module['image'];
                $image_alt = $image['altText'];
                $image_src = $image['src'];
        }

        $image_caption = $caption . ' ' . $image_credit
                    . '. Notes: Some image may have copyrighted on it.';
        $wrapper = <<<EOD
<figure>
<img src="{$image_src}" alt="{$image_alt}">
<figcaption>$image_caption</figcaption>
</figure>
EOD;
        return $wrapper;
    }

    private function getFullArticle($uri)
    {
        $html = getContents($uri);

        $scriptRegex = '/window\[\'__natgeo__\'\]=(.*);<\/script>/';

        preg_match($scriptRegex, $html, $matches, PREG_OFFSET_CAPTURE, 0);

        $json = json_decode($matches[1][0], true);

        if (isset($json['page']['content']['article']['frms'])) {
            $unfiltered_data = $json['page']['content']['article']['frms'];
        } else {
            $unfiltered_data = $json['page']['content']['prismarticle']['frms'];
        }
        $filtered_data = $this->filterArticleData($unfiltered_data);

        $article = $filtered_data['edgs'][0];

        $contributors = $article['cntrbGrp'];
        $authors = [];
        if (count($contributors) > 0) {
            $authors = $contributors[0]['contributors'];
        }

        $authors_name = '';
        $counter = 0;
        foreach ($authors as $author) {
            $counter++;
            if ($counter == count($authors)) {
                $authors_name .= $author['displayName'];
            } else {
                $authors_name .= $author['displayName'] . ', ';
            }
        }

        $published_date = $article['pbDt'] ?? $article['dt'];
        $article_body = $article['bdy'];
        $content = '';

        foreach ($article_body as $body) {
            switch ($body['type']) {
                case 'p':
                    $content .= '<p>' . $body['cntnt']['mrkup'] . '</p>';
                    break;
                case 'h2':
                    $content .= '<h2>' . $body['cntnt']['mrkup'] . '</h2>';
                    break;
                case 'inline':
                    $module = $body['cntnt'];
                    if (empty($module)) {
                        continue 2;
                    }
                    switch ($module['cmsType']) {
                        case 'image':
                            $content .= $this->handleImages($module, $module['cmsType']);
                            break;
                        case 'imagegroup':
                            $images = $module['images'];
                            foreach ($images as $image) {
                                $content .= $this->handleImages($image, $module['cmsType']);
                            }
                            break;
                        case 'editorsNote':
                            $content .= $module['note'];
                            break;
                        case 'listicle':
                            $content .= '<h2>' . ($module['title'] ?? '(no title)') . '</h2>';
                            if (isset($module['image'])) {
                                $content .= $this->handleImages($module['image'], $module['image']['cmsType']);
                            }
                            $content .= '<p>' . ($module['text'] ?? '') . '</p>';
                            break;
                        case 'photogallery':
                            $gallery = $body['cntnt']['media'];
                            foreach ($gallery as $image) {
                                $content .= $this->handleImages($image, $module['cmsType']);
                            }
                            break;
                        case 'video':
                            $content .= $this->handleImages($module, $module['cmsType']);
                            break;
                        case 'pullquote':
                            $quote = $module['quote'];
                            $author_name = '';
                            $authors = ($module['byLineProps']['authors'] ?? []);
                            foreach ($authors as $author) {
                                $author_desc = ($author['authorDesc'] ?? '');
                                $author_name .= $author['displayName'] . ', ' . $author_desc;
                            }
                            $content .= <<<EOD
<figure>
<blockquote>
<p>$quote</p>
</blockquote>
<figcaption>$author_name</figcaption>
</figure>
EOD;
                            break;
                    }
                    break;
                case 'ul':
                    $content .= $body['cntnt']['mrkup'] . '<hr>';
                    break;
            }
        }

        return [
            'content' => $content,
            'published_date' => $published_date,
            'authors' => $authors_name
        ];
    }
}
