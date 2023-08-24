<?php

class ImgsedBridge extends BridgeAbstract
{
    const MAINTAINER = 'sysadminstory';
    const NAME = 'Imgsed Bridge';
    const URI = 'https://imgsed.com/';
    const INSTAGRAMURI = 'https://www.instagram.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns Imgsed (Instagram viewer) content by user';

    const PARAMETERS = [
        'Username' => [
            'u' => [
                'name' => 'username',
                'type' => 'text',
                'title' => 'Instagram username you want to follow',
                'exampleValue' => 'aesoprockwins',
                'required' => true,
            ],
            'post' => [
                'name' => 'posts',
                'type' => 'checkbox',
                'title' => 'Show posts for this Instagram user',
                'defaultValue' => 'checked',
            ],
            'story' => [
                'name' => 'stories',
                'type' => 'checkbox',
                'title' => 'Show stories for this Instagram user',
            ],
            'tagged' => [
                'name' => 'tagged',
                'type' => 'checkbox',
                'title' => 'Show tagged post for this Instagram user',
            ],
        ]
    ];

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return urljoin(self::URI, '/' . $this->getInput('u') . '/');
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $username = $this->getInput('u');
        try {
            // Check if the user exist
            $html = getSimpleHTMLDOMCached(self::URI . $username . '/');
            if ($this->getInput('post')) {
                $this->collectPosts();
            }
            if ($this->getInput('story')) {
                $this->collectStories();
            }
            if ($this->getInput('tagged')) {
                $this->collectTaggeds();
            }
        } catch (HttpException $e) {
            throw new \Exception(sprintf('Unable to find user `%s`', $username));
        }
    }

    private function collectPosts()
    {
        $username = $this->getInput('u');
        $html = getSimpleHTMLDOMCached(self::URI . $username . '/');
        $html = defaultLinkTo($html, self::URI);

        foreach ($html->find('div[class=item]') as $post) {
            $url = $post->find('a', 0)->href;
            $instagramURL = $this->convertURLToInstagram($url);
            $date = $this->parseDate($post->find('div[class=time]', 0)->plaintext);
            $description = $post->find('img', 0)->alt;
            $imageUrl = $post->find('img', 0)->src;
            // Sometimes, there is some lazy image instead of the real URL
            if ($imageUrl == 'https://imgsed.com/img/lazy.jpg') {
                $imageUrl = $post->find('img', 0)->getAttribute('data-src');
            }
            $download = $post->find('a[class=download]', 0)->href;
            $author = $username;
            $uid = $post->find('a', 0)->href;
            $title = 'Post - ' . $username . ' - ' . $this->descriptionToTitle($description);

            // Checking post type
            $isVideo = (bool) $post->find('i[class=video]', 0);
            $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

            $isMoreContent = (bool) $post->find('svg', 0);
            $moreContentNote = $isMoreContent ? '<p><i>(multiple images and/or videos)</i></p>' : '';




            $this->items[] = [
                'uri'        => $url,
                'author'     => $author,
                'timestamp'  => $date,
                'title'      => $title,
                'thumbnail'  => $imageUrl,
                'enclosures' => [$imageUrl, $download],
                'content'    => <<<HTML
<a href="{$url}">
        <img loading="lazy" src="{$imageUrl}" alt="{$description}"/>
</a>
{$videoNote}
{$moreContentNote}
<p>{$description}<p>
<p><a href="{$download}">Download</a></p>
<p><a href="{$instagramURL}">Display on Instagram</a></p>
HTML,
                'uid' => $uid
            ];
        }
    }

    private function collectStories()
    {
        try {
            $username = $this->getInput('u');
            $html = getSimpleHTMLDOMCached(self::URI . 'api/media/?name=' . $username);
            $json = Json::decode($html);

            foreach ($json as $post) {
                $url = $post['src'];
                $imageUrl = $post['thumb'];
                $download = $url;
                $author = $username;
                $uid = $url;
                $title = 'Story - ' . $username;

                $this->items[] = [
                    'uri'        => $url,
                    'author'     => $author,
                    'title'      => $title,
                    'thumbnail'  => $imageUrl,
                    'enclosures' => [$imageUrl, $download],
                    'content'    => <<<HTML
    <a href="{$url}">
            <img loading="lazy" src="{$imageUrl}" alt="story"/>
    </a>
    <p><a href="{$download}">Download</a></p>
    HTML,
                    'uid' => $uid
                ];
            }
        } catch (Exception $e) {
            // If it fails, it's because there are no stories, so don't do anything
        }
    }

    private function collectTaggeds()
    {
        $username = $this->getInput('u');
        try {
            $html = getSimpleHTMLDOMCached(self::URI . 'tagged/' . $username . '/');
            $html = defaultLinkTo($html, self::URI);

            foreach ($html->find('div[class=item]') as $post) {
                $url = $post->find('a', 1)->href;
                $instagramURL = $this->convertURLToInstagram($url);
                $fromURL = $post->find('div[class=username]', 0)->find('a', 0)->href;
                $fromUsername = $post->find('div[class=username]', 0)->plaintext;
                $date = $this->parseDate($post->find('div[class=time]', 0)->plaintext);
                $description = $post->find('img', 0)->alt;
                $imageUrl = $post->find('img', 0)->src;
                $download = $post->find('a[class=download]', 0)->href;
                $author = $fromUsername;
                $uid = $post->find('a', 0)->href;
                $title = 'Tagged - ' . $fromUsername . ' - ' . $this->descriptionToTitle($description);

                // Checking post type
                $isVideo = (bool) $post->find('i[class=video]', 0);
                $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

                $isMoreContent = (bool) $post->find('svg', 0);
                $moreContentNote = $isMoreContent ? '<p><i>(multiple images and/or videos)</i></p>' : '';


                $this->items[] = [
                    'uri'        => $url,
                    'author'     => $author,
                    'timestamp'  => $date,
                    'title'      => $title,
                    'thumbnail'  => $imageUrl,
                    'enclosures' => [$imageUrl, $download],
                    'content'    => <<<HTML
<a href="{$url}">
        <img loading="lazy" src="{$imageUrl}" alt="{$description}"/>
</a>
{$videoNote}
{$moreContentNote}
<p>From <a href="{$fromURL}">{$fromUsername}</a></p>
<p>{$description}<p>
<p><a href="{$download}">Download</a></p>
<p><a href="{$instagramURL}">Display on Instagram</a></p>
HTML,
                    'uid' => $uid
                ];
            }
        } catch (Exception $e) {
            // If it fails, it's because the account was not tagged
        }
    }

    // Parse date, and transform the date into a timetamp, even in a case of a relative date
    private function parseDate($content)
    {
        $date = date_create();
        $dateString = str_replace(' ago', '', $content);
        $relativeDate = date_interval_create_from_date_string($dateString);
        if ($relativeDate) {
            date_sub($date, $relativeDate);
        } else {
            Logger::info(sprintf('Unable to parse date string: %s', $dateString));
        }
        return date_format($date, 'r');
    }

    private function convertURLToInstagram($url)
    {
        return str_replace(self::URI, self::INSTAGRAMURI, $url);
    }
    private function descriptionToTitle($description)
    {
        return strlen($description) > 60 ? mb_substr($description, 0, 57) . '...' : $description;
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            $types = [];
            if ($this->getInput('post')) {
                $types[] = 'Posts';
            }
            if ($this->getInput('story')) {
                $types[] = 'Stories';
            }
            if ($this->getInput('tagged')) {
                $types[] = 'Tags';
            }
            $typesText = $types[0];
            if (count($types) > 1) {
                for ($i = 1; $i < count($types) - 1; $i++) {
                    $typesText .= ', ' . $types[$i];
                }
                $typesText .= ' & ' . $types[$i];
            }

            return 'Username ' . $this->getInput('u') . ' - ' . $typesText . ' - Imgsed Bridge';
        }
        return parent::getName();
    }

    public function detectParameters($url)
    {
        $params = [
            'post' => 'on',
            'story' => 'on',
            'tagged' => 'on'
        ];
        $regex = '/^http(s|):\/\/((www\.|)(instagram.com)\/([a-zA-Z0-9_\.]{1,30})\/(reels\/|tagged\/|)
|(www\.|)(imgsed.com)\/(stories\/|tagged\/|)([a-zA-Z0-9_\.]{1,30})\/)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'Username';
            // Extract detected domain using the regex
            $domain = $matches[8] ?? $matches[4];
            if ($domain == 'imgsed.com') {
                $params['u'] = $matches[10];
                return $params;
            } else if ($domain == 'instagram.com') {
                $params['u'] = $matches[5];
                return $params;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
