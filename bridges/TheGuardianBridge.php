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
                'Australia News' => '/australia-news/rss',
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
        $feed = $this->getInput('feed');
        $feedURL = 'https://feeds.theguardian.com/theguardian/' . $feed;
        $this->collectExpandableDatas($feedURL, 10);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);

        // --- Recovering the article ---

        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);
        // figure contain's the main article image
        $article = $articlePage->find('figure', 0);
        // content__article-body has the actual article
        foreach ($articlePage->find('#maincontent') as $element) {
            $article = $article . $element;
        }

        // --- Fixing ugly elements ---

        // Replace the image viewer and BS with the image itself
        foreach ($articlePage->find('a.article__img-container') as $uslElementLoc) {
            $main_img = $uslElementLoc->find('img', 0);
            $article = str_replace($uslElementLoc, $main_img, $article);
        }

        // List of all the crap in the article
        $uselessElements = [
            'span > figcaption',
            '#show-caption',
            '.element-atom',
            '.submeta',
            'youtube-media-atom',
            'svg',
            '#the-checkbox',
        ];

        // Remove the listed crap
        foreach ($uselessElements as $uslElement) {
            foreach ($articlePage->find($uslElement) as $uslElementLoc) {
                $article = str_replace($uslElementLoc, '', $article);
            }
        }

        $item['content'] = $article;

        return $item;
    }
}
