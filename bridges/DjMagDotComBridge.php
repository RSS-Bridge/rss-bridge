<?php

class DjMagDotComBridge extends BridgeAbstract
{
    const NAME = 'DJMag.com News';
    const URI = 'https://www.djmag.com/';
    const DESCRIPTION = 'News from DJMag.com';
    const MAINTAINER = 'skrollme';
    const CACHE_TIMEOUT = 60*60; // 1 hours
    const PARAMETERS = [[
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'The number of news to get (max: 20)',
                'defaultValue' => 10
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://djmag.com/sites/default/files/favicons/favicon-32x32.png?v=2024';
    }

    public function getURI()
    {
        return self::URI . 'news';
    }

    private function _parseDateString($dateString)
    {
        // Expect formats like "30 December 2025, 12:10"
        $dateString = trim($dateString);

        // Try a strict parse first: day (no leading zero) monthname year, 24h:minute
        $dt = DateTime::createFromFormat('j F Y, H:i', $dateString);
        if ($dt instanceof DateTime) {
            return $dt->getTimestamp();
        }

        // Try with leading zero day
        $dt = DateTime::createFromFormat('d F Y, H:i', $dateString);
        if ($dt instanceof DateTime) {
            return $dt->getTimestamp();
        }

        // Fallback to strtotime which handles many human-readable formats
        $ts = strtotime($dateString);
        if ($ts !== false) {
            return $ts;
        }

        return null;
    }

    private function _fetchArticleDetails($uri, $image, $title)
    {
        $itemHtml = getSimpleHTMLDOM($uri);

        $content = '<h2>' . $itemHtml->find('article div.article--standfirst p', 0)->plaintext . '</h2><br>';
        $content .= '<img src="' . $image . '" alt="' . htmlentities($title) . '" /><p/>';
        $content .= '<p>' . trim(nl2br(htmlentities($itemHtml->find('article div.content-column-wrap-oh > div > div.field--name-field-content > div', 0)->plaintext))) . '</p>';

        $metaFields = $itemHtml->find('article div.pane-author-info', 1);
        // contains a timestamp in a format like 30 December 2025, 12:10
        $rawTimestamp = $metaFields->find('div', 1)->plaintext;
        $timestamp = $this->_parseDateString($rawTimestamp);

        $author = trim($metaFields->find('div', 0)->plaintext);

        return [$timestamp, $content, $author];
    }

    public function collectData()
    {
        $limit = max(0, min($this->getInput('limit'), 20));
        $url = $this->getUri();

        $mainHtml = getSimpleHTMLDOM($url);

        // fetch first/latest news item separately as it is structured differently due to being featured
        $firstNewsItemHtml = $mainHtml->find('div.attachment-before div.view-content', 0);
        $title = trim($firstNewsItemHtml->find('h1 > a', 0)->plaintext);
        $uri = self::URI . $firstNewsItemHtml->find('h1 > a', 0)->href;
        $image = rtrim(self::URI, '/') . $firstNewsItemHtml->find('.teaser-media source', 0)->srcset;

        list($timestamp, $content, $author) = $this->_fetchArticleDetails($uri, $image, $title);

        $this->items[] = [
            'title' => $title,
            'uri' => $uri,
            'uid' => sha1($uri),
            'thumbnail' => $image,
            'content' => $content,
            'timestamp' => $timestamp,
            'author' => $author,
            'categories' => ['NEWS'],
            'enclosures' => [$image],
        ];

        // continue with the rest of the news items
        foreach ($mainHtml->find('div#views-bootstrap-listing-news-page > div.row article') as $newsItem) {
            if ($limit-- <= 0) {
                break;
            }

            $title = trim($newsItem->find('h1 > a', 0)->plaintext);
            $uri = self::URI . $newsItem->find('a', 0)->href;
            $image = rtrim(self::URI, '/') . $newsItem->find('source', 0)->srcset;

            list($timestamp, $content, $author) = $this->_fetchArticleDetails($uri, $image, $title);

            $this->items[] = [
                'title' => $title,
                'uri' => $uri,
                'uid' => sha1($uri),
                'thumbnail' => $image,
                'content' => $content,
                'timestamp' => $timestamp,
                'author' => $author,
                'categories' => ['NEWS'],
                'enclosures' => [$image],
            ];
        }
    }
}
