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
        $this->collectExpandableDatas('https://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml', 40);
    }

    protected function parseItem($newsItem)
    {
        $item = parent::parseItem($newsItem);
        $article = '';

        // $articlePage gets the entire page's contents
        $articlePage = getSimpleHTMLDOM($newsItem->link);

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
