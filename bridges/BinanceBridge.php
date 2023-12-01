<?php

class BinanceBridge extends BridgeAbstract
{
    const NAME = 'Binance Blog';
    const URI = 'https://www.binance.com/en/blog';
    const DESCRIPTION = 'Subscribe to the Binance blog.';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 3600; // 1h

    public function collectData()
    {
        $url = 'https://www.binance.com/bapi/composite/v1/public/content/blog/list?category=&tag=&page=1&size=12';
        $json = getContents($url);
        $data = Json::decode($json, false);
        foreach ($data->data->blogList as $post) {
            $item = [];
            $item['title'] = $post->title;
            // Url slug not in json
            //$item['uri'] = $uri;
            $item['timestamp'] = $post->postTimeUTC / 1000;
            $item['author'] = 'Binance';
            $item['content'] = $post->brief;
            //$item['categories'] = $category;
            $item['uid'] = $post->idStr;
            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return 'https://bin.bnbstatic.com/static/images/common/favicon.ico';
    }
}
