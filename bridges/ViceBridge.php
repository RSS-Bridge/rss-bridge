<?php

class ViceBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'Vice Bridge';
    const URI = 'https://www.vice.com/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'RSS feed for vice publications like Vice News, Munchies, Motherboard, etc.';
    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'Vice News' => 'rss',
                'Motherboard - Tech' => 'en_us/rss/topic/tech',
                'Entertainment' => 'en_us/rss/topic/entertainment',
                'Noisey - Music' => 'en_us/rss/topic/music',
                'Munchies - Food' => 'en_us/rss/topic/food'
            ]
        ]
    ]];

    public function collectData()
    {
        $feed = $this->getInput('feed');
        if ($feed === 'rss') {
            // They changed url in Sep 2023
            $feed = 'en/rss';
        }
        $feedURL = 'https://www.vice.com/' . $feed;
        $this->collectExpandableDatas($feedURL, 10);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);
        // text and embedded content
        $article = $articlePage->find('.article__body', 0);
        $item['content'] = $article;

        return $item;
    }
}
