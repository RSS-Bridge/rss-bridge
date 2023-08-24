<?php

class WiredBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
    const NAME = 'WIRED Bridge';
    const URI = 'https://www.wired.com/';
    const DESCRIPTION = 'Returns the newest articles from WIRED';

    const PARAMETERS = [ [
        'feed' => [
            'name' => 'Feed',
            'type' => 'list',
            'values' => [
                'WIRED Top Stories' => 'rss',           // /feed/rss
                'Business' => 'business',               // /feed/category/business/latest/rss
                'Culture' => 'culture',                 // /feed/category/culture/latest/rss
                'Gear' => 'gear',                       // /feed/category/gear/latest/rss
                'Ideas' => 'ideas',                     // /feed/category/ideas/latest/rss
                'Science' => 'science',                 // /feed/category/science/latest/rss
                'Security' => 'security',               // /feed/category/security/latest/rss
                'Transportation' => 'transportation',   // /feed/category/transportation/latest/rss
                'Backchannel' => 'backchannel',         // /feed/category/backchannel/latest/rss
                'WIRED Guides' => 'wired-guide',        // /feed/tag/wired-guide/latest/rss
                'Photo' => 'photo'                      // /feed/category/photo/latest/rss
            ]
        ],
        'limit' => self::LIMIT,
    ]];

    public function collectData()
    {
        $feed = $this->getInput('feed');
        if (empty($feed) || !ctype_alpha(str_replace('-', '', $feed))) {
            returnClientError('Invalid feed, please check the "feed" parameter.');
        }

        $feed_url = $this->getURI() . 'feed/';
        if ($feed != 'rss') {
            if ($feed != 'wired-guide') {
                $feed_url .= 'category/';
            } else {
                $feed_url .= 'tag/';
            }
            $feed_url .= "$feed/latest/";
        }
        $feed_url .= 'rss';

        $limit = $this->getInput('limit') ?? -1;
        $this->collectExpandableDatas($feed_url, $limit);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        $article = getSimpleHTMLDOMCached($item['uri']);
        $item['content'] = $this->extractArticleContent($article);

        $headline = strval($newsItem->description);
        if (!empty($headline)) {
            $item['content'] = '<p><b>' . $headline . '</b></p>' . $item['content'];
        }

        $item_image = $article->find('meta[property="og:image"]', 0);
        if (!empty($item_image)) {
            $item['enclosures'] = [$item_image->content];
            $item['content'] = '<p><img src="' . $item_image->content . '" /></p>' . $item['content'];
        }

        return $item;
    }

    private function extractArticleContent($article)
    {
        $content = $article->find('article', 0);
        $truncate = true;

        if (empty($content)) {
            $content = $article->find('div.listicle-main-component__container', 0);
            $truncate = false;
        }

        if (!empty($content)) {
            $content = $content->innertext;
        }

        foreach (
            [
            '<div class="content-header',
            '<div class="mid-banner-wrap',
            '<div class="related',
            '<div class="social-icons',
            '<div class="recirc-most-popular',
            '<div class="grid--item article-related-video',
            '<div class="row full-bleed-ad',
            ] as $div_start
        ) {
            $content = stripRecursiveHTMLSection($content, 'div', $div_start);
        }

        if ($truncate) {
            //Clutter after standard article is too hard to clean properly
            $content = trim(explode('<hr', $content)[0]);
        }

        $content = str_replace('href="/', 'href="' . $this->getURI() . '/', $content);

        return $content;
    }
}
