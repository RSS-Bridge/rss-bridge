<?php

class CarThrottleBridge extends FeedExpander
{
    const NAME = 'Car Throttle ';
    const URI = 'https://www.carthrottle.com';
    const DESCRIPTION = 'Get the latest car-related news from Car Throttle.';
    const MAINTAINER = 't0stiman';

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.carthrottle.com/rss', 10);
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        //fetch page
        $articlePage = getSimpleHTMLDOMCached($feedItem->link)
            or returnServerError('Could not retrieve ' . $feedItem->link);

        $subtitle = $articlePage->find('p.standfirst', 0);
        $article = $articlePage->find('div.content_field', 0);

        $item['content'] = str_get_html($subtitle . $article);

        //convert <iframe>s to <a>s. meant for embedded videos.
        foreach ($item['content']->find('iframe') as $found) {
            $iframeUrl = $found->getAttribute('src');

            if ($iframeUrl) {
                $found->outertext = '<a href="' . $iframeUrl . '">' . $iframeUrl . '</a>';
            }
        }

        //remove scripts from the text
        foreach ($item['content']->find('script') as $remove) {
            $remove->outertext = '';
        }

        return $item;
    }
}
