<?php

class GreatFonBridge extends BridgeAbstract
{
    const MAINTAINER = 'sysadminstory';
    const NAME = 'GreatFon Bridge';
    const URI = 'https://greatfon.com/';
    const INSTAGRAMURI = 'https://www.instagram.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns GreatFon (Instagram viewer) content by user';

    const PARAMETERS = [
        'Username' => [
            'u' => [
                'name' => 'username',
                'type' => 'text',
                'title' => 'Instagram username you want to follow',
                'exampleValue' => 'aesoprockwins',
                'required' => true,
            ],
        ]
    ];
    const TEST_DETECT_PARAMETERS = [
        'https://www.instagram.com/instagram/' => ['context' => 'Username', 'u' => 'instagram'],
        'https://instagram.com/instagram/' => ['context' => 'Username', 'u' => 'instagram'],
        'https://greatfon.com/v/instagram' => ['context' => 'Username', 'u' => 'instagram'],
        'https://www.greatfon.com/v/instagram' => ['context' => 'Username', 'u' => 'instagram'],
    ];

    public function collectData()
    {
        $username = $this->getInput('u');
        $html = getSimpleHTMLDOMCached(self::URI . '/v/' . $username);
        $html = defaultLinkTo($html, self::URI);

        foreach ($html->find('div[class*=content__item]') as $post) {
            // Skip the ads
            if (!str_contains($post->class, 'ads')) {
                $url = $post->find('a[href^=https://greatfon.com/c/]', 0)->href;
                $date = $this->parseDate($post->find('div[class=content__time-text]', 0)->plaintext);
                $description = $post->find('img', 0)->alt;
                $imageUrl = $post->find('img', 0)->src;
                $author = $username;
                $uid = $url;
                $title = 'Post - ' . $username . ' - ' . $this->descriptionToTitle($description);

                // Checking post type
                $isVideo = (bool) $post->find('div[class=content__camera]', 0);
                $videoNote = $isVideo ? '<p><i>(video)</i></p>' : '';

                $this->items[] = [
                    'uri'        => $url,
                    'author'     => $author,
                    'timestamp'  => $date,
                    'title'      => $title,
                    'thumbnail'  => $imageUrl,
                    'enclosures' => [$imageUrl],
                    'content'    => <<<HTML
<a href="{$url}">
        <img loading="lazy" src="{$imageUrl}" alt="{$description}"/>
</a>
{$videoNote}
<p>{$description}<p>
HTML,
                    'uid' => $uid
                ];
            }
        }
    }

    private function parseDate($content)
    {
        // Parse date, and transform the date into a timetamp, even in a case of a relative date
        $date = date_create();

        // Content trimmed to be sure that the "article" is at the beginning of the string and remove "ago" to make it a valid PHP date interval
        $dateString = trim(str_replace(' ago', '', $content));

        // Replace the article "an" or "a" by the number "1" to be a valid PHP date interval
        $dateString = preg_replace('/^((an|a) )/m', '1 ', $dateString);

        $relativeDate = date_interval_create_from_date_string($dateString);
        if ($relativeDate) {
            date_sub($date, $relativeDate);
            // As the relative interval has the precision of a day for date older than 24 hours, we can remove the hour of the date, as it is not relevant
            date_time_set($date, 0, 0, 0, 0);
        } else {
            $this->logger->info(sprintf('Unable to parse date string: %s', $dateString));
        }
        return date_format($date, 'r');
    }

    public function getURI()
    {
        if (!is_null($this->getInput('u'))) {
            return urljoin(self::URI, '/v/' . $this->getInput('u'));
        }

        return parent::getURI();
    }

    public function getIcon()
    {
        return static::URI . '/images/favicon-hub-3ede543aa6d1225e8dc016ccff6879c8.ico?vsn=d';
    }

    private function descriptionToTitle($description)
    {
        return strlen($description) > 60 ? mb_substr($description, 0, 57) . '...' : $description;
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return 'Username ' . $this->getInput('u') . ' - GreatFon Bridge';
        }
        return parent::getName();
    }

    public function detectParameters($url)
    {
        $regex = '/^http(s|):\/\/((www\.|)(instagram.com)\/([a-zA-Z0-9_\.]{1,30})(\/reels\/|\/tagged\/|\/|)|(www\.|)(greatfon.com)\/v\/([a-zA-Z0-9_\.]{1,30}))/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'Username';
            // Extract detected domain using the regex
            $domain = $matches[8] ?? $matches[4];
            if ($domain == 'greatfon.com') {
                $params['u'] = $matches[9];
                return $params;
            } elseif ($domain == 'instagram.com') {
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
