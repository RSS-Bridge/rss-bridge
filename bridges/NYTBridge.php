<?php

class NYTBridge extends FeedExpander
{
    const MAINTAINER = 'IceWreck';
    const NAME = 'New York Times Bridge';
    const URI = 'https://www.nytimes.com/';
    const CACHE_TIMEOUT = 900; // 15 minutes
    const DESCRIPTION = 'RSS feed for the New York Times';

    public function collectData()
    {
        $url = 'https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml';
        $this->collectExpandableDatas($url, 40);
    }

    protected function parseItem(array $item)
    {
        $article = '';

        try {
            $articlePage = getSimpleHTMLDOM($item['uri']);
        } catch (HttpException $e) {
            // 403 Forbidden, This means we got anti-bot response
            if ($e->getCode() === 403) {
                return $item;
            }
            throw $e;
        }
        // handle subtitle
        $subtitle = $articlePage->find('p.css-w6ymp8', 0);
        if ($subtitle != null) {
            $article .= '<strong>' . $subtitle->plaintext . '</strong>';
        }

        // figure contain's the main article image
        $article .= $articlePage->find('figure', 0) . '<br />';

        // section.meteredContent has the actual article
        foreach ($articlePage->find('section.meteredContent p') as $element) {
            $article .= '' . $element . '';
        }

        $item['content'] = $article;
        return $item;
    }
}
