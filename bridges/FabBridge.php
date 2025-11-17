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
        $url = static::URI . '/i/blades/free_content_blade';

        $header = [
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Referer: ' . static::URI
        ];

        $json = getContents($url, $header);
        $json = json_decode($json);

        foreach ($json->tiles as $item) {
            $thumbnail = $item->listing->thumbnails[0]->mediaUrl;
            $itemurl = static::URI . '/listings/' . $item->listing->uid;

            $this->items[] = [
                'title' => $item->listing->title,
                'author' => $item->listing->user->sellerName,
                'uri' => $itemurl,
                'timestamp' => strtotime($item->listing->lastUpdatedAt),
                'content' => '<a href="' . $itemurl . '"><img src="' . $thumbnail . '"></a>' . $item->listing->description,
            ];
        }
    }
}
