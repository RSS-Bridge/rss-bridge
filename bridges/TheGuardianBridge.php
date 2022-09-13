<?php

class TheGuardianBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'The Guardian Bridge';
    const URI = 'https://www.theguardian.com/';
    const CACHE_TIMEOUT = 600; // This is a news site, so don't cache for more than 10 mins
    const DESCRIPTION = 'RSS feed for The Guardian';
    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'World News' => 'world/rss',
                'US News' => '/us-news/rss',
                'UK News' => '/uk-news/rss',
                'Europe News' => '/world/europe-news/rss',
                'Asia News' => '/world/asia/rss',
                'Tech' => '/uk/technology/rss',
                'Business News' => '/uk/business/rss',
                'Opinion' => '/uk/commentisfree/rss',
                'Lifestyle' => '/uk/lifeandstyle/rss',
                'Culture' => '/uk/culture/rss',
                'Sports' => '/uk/sport/rss'
            ]
        ]

        /*

        Topicwise Links

        You can find the base feed for any topic by appending /rss to the url.

        Example:

        https://feeds.theguardian.com/theguardian/uk-news/rss
        https://feeds.theguardian.com/theguardian/us-news/rss

        Or simply

        https://www.theguardian.com/world/rss

        Just add that topic as a value in the PARAMETERS const.

        */


    ]];

    public function collectData()
    {
        $url = sprintf('https://feeds.theguardian.com/theguardian/%s', $this->getInput('feed'));
        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        $dom = getSimpleHTMLDOM($newsItem->link);
        // figure contain's the main article image
        $figure = $dom->find('figure', 0);
        $mainImage = $figure->find('img', 0);
        $article = '';
        if ($mainImage->src) {
            $article .= $mainImage . '<br>';
        }
        $body = $dom->find('.article-body-commercial-selector', 0);
        $article .= $body;
        // Replace the image viewer and BS with the image itself
        foreach ($body->find('a.article__img-container') as $uslElementLoc) {
            $main_img = $uslElementLoc->find('img', 0);
            $article = str_replace($uslElementLoc, $main_img, $article);
        }
        // Remove annoying stuff
        $annoyingStuffs = [
            '#show-caption',
            '.element-atom',
            '.submeta',
            'youtube-media-atom',
            'svg',
            'figcaption',
        ];
        foreach ($annoyingStuffs as $annoyingStuff) {
            foreach ($dom->find($annoyingStuff) as $annoying) {
                $article = str_replace($annoying, '', $article);
            }
        }
        $item['content'] = $article;
        return $item;
    }
}
