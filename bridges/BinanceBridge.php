<?php

class BinanceBridge extends BridgeAbstract
{
    const NAME = 'Binance Blog';
    const URI = 'https://www.binance.com/en/blog';
    const DESCRIPTION = 'Subscribe to the Binance blog.';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 3600; // 1h

    public function getIcon()
    {
        return 'https://bin.bnbstatic.com/static/images/common/favicon.ico';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not fetch Binance blog data.');

        $appData = $html->find('script[id="__APP_DATA"]');
        $appDataJson = json_decode($appData[0]->innertext);
        $allposts = $appDataJson->routeProps->f3ac->blogListRes->list;

        foreach ($allposts as $element) {
            $date = $element->releasedTime;
            $title = $element->title;
            $category = $element->category->name;

            $suburl = strtolower($category);
            $suburl = str_replace(' ', '_', $suburl);

            $uri = self::URI . '/' . $suburl . '/' . $element->idStr;

            $contentHTML = getSimpleHTMLDOMCached($uri);
            $contentAppData = $contentHTML->find('script[id="__APP_DATA"]');
            $contentAppDataJson = json_decode($contentAppData[0]->innertext);
            $content = $contentAppDataJson->routeProps->a106->blogDetail->content;

            $item = [];
            $item['title'] = $title;
            $item['uri'] = $uri;
            $item['timestamp'] = substr($date, 0, -3);
            $item['author'] = 'Binance';
            $item['content'] = $content;
            $item['categories'] = $category;

            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }
}
