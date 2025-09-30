<?php

class AkamaiBridge extends FeedExpander
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Akamai Blog';
    const URI = 'https://www.akamai.com/blog';
    const DESCRIPTION = 'Akamai CDN Blog';
    const PARAMETERS = [[
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 5
        ]
    ]];

    const FEED_URI = 'https://feeds.feedburner.com/akamai/blog';
    const HEADERS = [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:140.0) Gecko/20100101 Firefox/140.0',
        'Accept-Language: en',
    ];

    public function collectData()
    {
        $this->collectExpandableDatas(
            self::FEED_URI,
            $this->getInput('limit') ?? static::LIMIT
        );
    }

    protected function parseItem(array $item)
    {
        $page = getSimpleHTMLDOMCached($item['uri'], self::CACHE_TIMEOUT, self::HEADERS);
        $page = defaultLinkTo($page, $item['uri']);

        if (!$page) {
            return $item;
        }

        $article = $page->find('section.main-content', 0);
        if (!$article) {
            return $item;
        }

        // Extract categories/tags
        foreach ($article->find('.taglist .cmp-tag-list__list-item') as $tag) {
            $item['categories'][] = $tag->plaintext;
        }

        // Remove annoying elements
        foreach ($article->find('.socialshare, .blogauthor, .taglist, .cmp-prismjs__copy') as $elem) {
            $elem->remove();
        }
        foreach ($article->find('p') as $elem) {
            if ($elem->plaintext === 'Tags') {
                $elem->remove();
            }
        }

        // Replace content with full text
        $item['content'] = $article->innertext;

        return $item;
    }

    public function getIcon()
    {
        return 'https://www.akamai.com/site/favicon/android-chrome-192x192.png';
    }
}
