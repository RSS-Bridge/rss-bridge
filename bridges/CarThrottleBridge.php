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

            $authorDiv = $articlePage->find('div.author div');
            if ($authorDiv) {
                $item['author'] = $authorDiv[1]->innertext;
            }

            $dinges = $articlePage->find('div.main-body')[0] ?? null;
            //remove ads
            if ($dinges) {
                foreach ($dinges->find('aside') as $ad) {
                    $ad->outertext = '';
                    $dinges->save();
                }
            }

            $var = $articlePage->find('div.summary')[0] ?? '';
            $var1 = $articlePage->find('figure.main-image')[0] ?? '';
            $dinges1 = $dinges ?? '';

            $item['content'] = $var .
                $var1 .
                $dinges1;

            array_push($this->items, $item);
        }
    }
}
