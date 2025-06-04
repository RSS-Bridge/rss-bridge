<?php

class FabBridge extends BridgeAbstract
{
    const NAME = 'Epic Games Fab.com';
    const URI = 'https://www.fab.com';
    const DESCRIPTION = 'Limited-Time Free Game Engine Assets';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400;

    public function collectData()
    {
        $url = static::URI . '/i/listings/search?is_discounted=1&is_free=1';

        $header = [
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Referer: ' . static::URI
        ];

        $json = getContents($url, $header);
        $json = json_decode($json);

        foreach ($json->results as $item) {
            $thumbnail = $item->thumbnails[0]->mediaUrl;
            $itemurl = static::URI . '/listings/' . $item->uid;

            $itemapiurl = static::URI . '/i/listings/' . $item->uid;
            $itemjson = getContents($itemapiurl, $header);
            $itemjson = json_decode($itemjson);

            $this->items[] = [
                'title' => $item->title,
                'author' => $item->user->sellerName,
                'uri' => $itemurl,
                'timestamp' => strtotime($item->lastUpdatedAt),
                'content' => '<a href="' . $itemurl . '"><img src="' . $thumbnail . '"></a>' . $itemjson->description,
            ];
        }
    }
}
