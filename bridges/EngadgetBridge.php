<?php

class EngadgetBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'Engadget Bridge';
    const URI = 'https://www.engadget.com/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Article content for Engadget.';

    public function collectData()
    {
        $max = 10;
        $this->collectExpandableDatas(static::URI . 'rss.xml', $max);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        $url = (string) $newsItem->link;
        if (!$url) {
            return $item;
        }
        // todo: remove querystring tracking
        $articlePage = getSimpleHTMLDOM($url);
        // figure contain's the main article image
        $article = $articlePage->find('figure', 0);
        // .article-text has the actual article
        foreach ($articlePage->find('.article-text') as $element) {
            $article = $article . $element;
        }
        $item['content'] = $article;
        return $item;
    }
}
