<?php

class VideoCardzBridge extends BridgeAbstract
{
    const NAME = 'VideoCardz';
    const URI = 'https://videocardz.com/';
    const DESCRIPTION = 'Returns news from VideoCardz.com';
    const MAINTAINER = 'rmscoelho';
    const CACHE_TIMEOUT = 300;
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from VideoCardz.com',
                'values' => [
                    'News' => 'sections/news',
                    'Featured' => 'sections/featured',
                    'Leak' => 'sections/leak',
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

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'feed':
                $url = self::URI . $this->getInput('feed');
                break;
            default:
                $url = self::URI;
        }
        return $url;
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
            $image = $article -> style;
            $image = preg_replace('/background-image:url\(/i', '', $image);
            $image = substr_replace($image ,"", -3);

            //Get date and time of publishing
            $datetime = date_parse($article->find('.main-index-article-datetitle-date > a', 0)->plaintext);
            $year = $datetime['year'];
            $month = $datetime['month'];
            $day = $datetime['day'];
            $hour = $datetime['hour'];
            $minute = $datetime['minute'];
            $timestamp = mktime($hour, $minute, 0, $month, $day, $year);

            $this->items[] = [
                'title' => $article->find('h2', 0)->plaintext,
                'uri' => $article->find('p.main-index-article-datetitle-date > a', 0)->href,
                'content' => "<img src='".$image."' alt='".$article->find('h2', 0)->plaintext." thumbnail' />",
                'timestamp' => $timestamp,
            ];
        }
    }
}
