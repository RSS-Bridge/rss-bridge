<?php

class FreeCodeCampBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'FreeCodecamp Bridge';
    const URI = 'https://www.freecodecamp.org';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'RSS feed for FreeCodeCamp';
    // Freecodecamp removed their old full content rss feed and replaced it with one liner content.

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.freecodecamp.org/news/rss/', 15);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);
        // figure contain's the main article image
        $article = $articlePage->find('figure', 0);
        // the actual article
        foreach ($articlePage->find('.post-full-content') as $element) {
            $article = $article . $element;
        }
        $item['content'] = $article;
        return $item;
    }
}
