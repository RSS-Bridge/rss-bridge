<?php

class TheOatmealBridge extends FeedExpander
{
    const NAME = 'The Oatmeal';
    const URI = 'https://theoatmeal.com/';
    const DESCRIPTION = 'Fetch the entire comic image';
    const MAINTAINER = 't0stiman';
    const DONATION_URI = 'https://ko-fi.com/tostiman';

    public function collectData()
    {
        $url = self::URI . 'feed/rss';
        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem(array $item)
    {
        $page = getSimpleHTMLDOMCached($item['uri']);
        $comicImage = $page->find('#comic > p > img', 0);
        $item['content'] = $comicImage;

        return $item;
    }
}