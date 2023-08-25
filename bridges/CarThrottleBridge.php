<?php

class CarThrottleBridge extends BridgeAbstract
{
    const NAME = 'Car Throttle';
    const URI = 'https://www.carthrottle.com/';
    const DESCRIPTION = 'Get the latest car-related news from Car Throttle.';
    const MAINTAINER = 't0stiman';

    public function collectData()
    {
        $news = getSimpleHTMLDOMCached(self::URI . 'news')
            or returnServerError('could not retrieve page');

        $this->items[] = [];

        //for each post
        foreach ($news->find('div.cmg-card') as $post) {
            $item = [];

            $titleElement = $post->find('div.title a.cmg-link')[0];
            $item['uri'] = self::URI . $titleElement->getAttribute('href');
            $item['title'] = $titleElement->innertext;

            $articlePage = getSimpleHTMLDOMCached($item['uri'])
                or returnServerError('could not retrieve page');

            $item['author'] = $articlePage->find('div.author div')[1]->innertext;

            $dinges = $articlePage->find('div.main-body')[0];
            //remove ads
            foreach ($dinges->find('aside') as $ad) {
                $ad->outertext = '';
                $dinges->save();
            }

            $item['content'] = $articlePage->find('div.summary')[0] .
                $articlePage->find('figure.main-image')[0] .
                $dinges;

            //add the item to the list
            array_push($this->items, $item);
        }
    }
}
