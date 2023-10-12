<?php

class VarietyBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'Variety Bridge';
    const URI = 'https://variety.com';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'RSS feed for Variety';

    public function collectData()
    {
        $this->collectExpandableDatas('https://feeds.feedburner.com/variety/headlines', 15);
    }

    protected function parseItem(array $item)
    {
        $articlePage = getSimpleHTMLDOM($item['uri']);

        // Remove Script tags
        foreach ($articlePage->find('script') as $script_tag) {
            $script_tag->remove();
        }
        $article = $articlePage->find('div.c-featured-media', 0);
        $article = $article . $articlePage->find('.c-content', 0);

        $item['content'] = $article;

        return $item;
    }
}
