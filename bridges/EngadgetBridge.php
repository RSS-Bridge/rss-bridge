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
        $url = 'https://www.engadget.com/rss.xml';
        $max = 10;
        $this->collectExpandableDatas($url, $max);
    }

    protected function parseItem(array $item)
    {
        $itemUrl = trim($item['uri']);
        if (!$itemUrl) {
            return $item;
        }
        // todo: remove querystring tracking
        $dom = getSimpleHTMLDOM($itemUrl);
        // figure contain's the main article image
        $article = $dom->find('figure', 0);
        // .article-text has the actual article
        foreach ($dom->find('.article-text') as $element) {
            $article = $article . $element;
        }
        $item['content'] = $article ?? '';
        return $item;
    }
}
