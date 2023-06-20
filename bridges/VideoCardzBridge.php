<?php

class VideoCardzBridge extends BridgeAbstract
{
    const NAME = 'VideoCardz';
    const URI = 'https://videocardz.com/';
    const DESCRIPTION = 'Returns news from VideoCardz.com';
    const MAINTAINER = 'rmscoelho';
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from VideoCardz.com',
                'values' => [
                    'News' => 'sections/news',
                    'Featured' => 'sections/featured',
                    'Leaks' => 'sections/leaks',
                    'Press Releases' => 'sections/press-releases',
                    'Preview Roundup' => 'sections/review-roundup',
                    'Rumour' => 'sections/rumor',
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://videocardz.com/favicon-32x32.png?x66580';
    }

    public function getName()
    {
        $feed = $this->getInput( 'feed');
        $feed = explode('/', $feed);
        $feed = $feed[1];
        if (str_contains($feed, '-')) {
            $feed = explode('-', $feed);
            $word1 = $feed[0];
            $word2 = $feed[1];
            $feed = ucfirst($word1) . ' ' . ucfirst($word2);
        }
        if ($this->getInput('feed') !== null && $this->getInput('feed') !== '') {
            return self::NAME . ' | ' . $feed;
        }
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('feed');
    }

    public function collectData()
    {
        $url = sprintf('https://videocardz.com/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('.subcategory-news', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());

        foreach ($dom->find('article') as $article) {
            //Get thumbnail
            $image = $article->style;
            $image = preg_replace('/background-image:url\(/i', '', $image);
            $image = substr_replace($image, '', -3);
            //Get date and time of publishing
            $datetime = date_parse($article->find('.main-index-article-datetitle-date > a', 0)->plaintext);
            $year = $datetime['year'];
            $month = $datetime['month'];
            $day = $datetime['day'];
            $hour = $datetime['hour'];
            $minute = $datetime['minute'];
            $timestamp = mktime($hour, $minute, 0, $month, $day, $year);

            $content = '<img src="' . $image . '" alt="' . $article->find('h2', 0)->plaintext . ' thumbnail" />';

            $this->items[] = [
                'title' => $article->find('h2', 0)->plaintext,
                'uri' => $article->find('p.main-index-article-datetitle-date > a', 0)->href,
                'content' => $content,
                'timestamp' => $timestamp,
            ];
        }
    }
}
