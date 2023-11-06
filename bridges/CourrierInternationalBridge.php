<?php

class CourrierInternationalBridge extends FeedExpander
{
    const MAINTAINER = 'teromene';
    const NAME = 'Courrier International Bridge';
    const URI = 'https://www.courrierinternational.com/';
    const CACHE_TIMEOUT = 300; // 5 min
    const DESCRIPTION = 'Returns the newest articles';

    public function collectData()
    {
        $this->collectExpandableDatas(static::URI . 'feed/all/rss.xml', 20);
    }

    protected function parseItem(array $item)
    {
        $articlePage = getSimpleHTMLDOMCached($item['uri']);
        $content = $articlePage->find('.article-text, depeche-text', 0);
        if (!$content) {
            return $item;
        }
        $item['content'] = sanitize($content);

        return $item;
    }
}
