<?php

class ListverseBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'Listverse Bridge';
    const URI = 'https://listverse.com/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'RSS feed for Listverse';

    public function collectData()
    {
        $this->collectExpandableDatas('https://listverse.com/feed/', 15);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);
        $article = $articlePage->find('#articlecontentonly', 0);
        $item['content'] = $article;
        return $item;
    }
}
