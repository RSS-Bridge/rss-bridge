<?php

class MediapartBridge extends FeedExpander
{
    const MAINTAINER = 'killruana';
    const NAME = 'Mediapart Bridge';
    const URI = 'https://www.mediapart.fr/';
    const PARAMETERS = [
        [
            'single_page_mode' => [
                'name' => 'Single page article',
                'type' => 'checkbox',
                'title' => 'Display long articles on a single page',
                'defaultValue' => 'checked'
            ],
            'mpsessid' => [
                'name' => 'MPSESSID',
                'type' => 'text',
                'title' => 'Value of the session cookie MPSESSID'
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Returns the newest articles.';

    public function collectData()
    {
        $url = self::URI . 'articles/feed';
        $this->collectExpandableDatas($url);
    }

    protected function parseItem(array $item)
    {
        $itemUrl = $item['uri'];

        // Mediapart provide multiple type of contents.
        // We only process items relative to the newspaper
        // See issue #1292 - https://github.com/RSS-Bridge/rss-bridge/issues/1292
        if (strpos($item['uri'], self::URI . 'journal/') === 0) {
            // Enable single page mode?
            if ($this->getInput('single_page_mode') === true) {
                $item['uri'] .= '?onglet=full';
            }

            // If a session cookie is defined, get the full article
            $mpsessid = $this->getInput('mpsessid');
            if (!empty($mpsessid)) {
                // Set the session cookie
                $opt = [];
                $opt[CURLOPT_COOKIE] = 'MPSESSID=' . $mpsessid;

                $pageUrl = $itemUrl . '?onglet=full';
                $articlePage = getSimpleHTMLDOM($pageUrl, [], $opt);

                // Extract the article content
                $content = $articlePage->find('div.content-article', 0)->innertext;
                $content = sanitize($content);
                $content = defaultLinkTo($content, static::URI);
                $item['content'] .= $content;
            }
        }

        return $item;
    }
}
