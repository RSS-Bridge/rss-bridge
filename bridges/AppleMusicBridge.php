<?php

class AppleMusicBridge extends BridgeAbstract
{
    const NAME = 'Apple Music';
    const URI = 'https://www.apple.com';
    const DESCRIPTION = 'Fetches the latest releases from an artist';
    const MAINTAINER = 'bockiii';
    const PARAMETERS = [[
        'artist' => [
            'name' => 'Artist ID',
            'exampleValue' => '909253',
            'required' => true,
        ],
        'limit' => [
            'name' => 'Latest X Releases (max 50)',
            'defaultValue' => '10',
            'required' => true,
        ],
    ]];
    const CACHE_TIMEOUT = 21600; // 6 hours

    public function collectData()
    {
        # Limit the amount of releases to 50
        if ($this->getInput('limit') > 50) {
            $limit = 50;
        } else {
            $limit = $this->getInput('limit');
        }

        $url = 'https://itunes.apple.com/lookup?id='
            . $this->getInput('artist')
            . '&entity=album&limit='
            . $limit .
            '&sort=recent';
        $html = getSimpleHTMLDOM($url);

        $json = json_decode($html);

        foreach ($json->results as $obj) {
            if ($obj->wrapperType === 'collection') {
                $this->items[] = [
                    'title' => $obj->artistName . ' - ' . $obj->collectionName,
                    'uri' => $obj->collectionViewUrl,
                    'timestamp' => $obj->releaseDate,
                    'enclosures' => $obj->artworkUrl100,
                    'content' => '<a href=' . $obj->collectionViewUrl
                    . '><img src="' . $obj->artworkUrl100 . '" /></a><br><br>'
                    . $obj->artistName . ' - ' . $obj->collectionName
                    . '<br>'
                    . $obj->copyright,
                ];
            }
        }
    }
}
