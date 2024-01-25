<?php

class CarThrottleBridge extends BridgeAbstract
{
    const NAME = 'Car Throttle';
    const URI = 'https://www.carthrottle.com/';
    const DESCRIPTION = 'Get the latest car-related news from Car Throttle.';
    const MAINTAINER = 't0stiman';
    const DONATION_URI = 'https://ko-fi.com/tostiman';

    public function collectData()
    {
        $news = getSimpleHTMLDOMCached(self::URI . 'news');

        $this->items = [];

        //for each post
        foreach ($news->find('div.cmg-card') as $post) {
            $item = [];

            $titleElement = $post->find('div.title a')[0];
            $post_uri = self::URI . $titleElement->getAttribute('href');

            if (!isset($post_uri) || $post_uri == '') {
                continue;
            }

            $item['uri'] = $post_uri;
            $item['title'] = $titleElement->innertext;

            $articlePage = getSimpleHTMLDOMCached($item['uri']);

            $authorDiv = $articlePage->find('div address');
            if ($authorDiv) {
                $item['author'] = $authorDiv[0]->innertext;
            }

            $fullArticle = $articlePage->find('article')[0];
            //remove ads
            foreach ($fullArticle->find('aside') as $ad) {
                $ad->outertext = '';
                $fullArticle->save();
            }

            $summary = $fullArticle->find('div.summary')[0];
            $articleMain = $fullArticle->find('#lbs-content')[0];

            $item['content'] = $summary . $articleMain;

            array_push($this->items, $item);
        }
    }
}
