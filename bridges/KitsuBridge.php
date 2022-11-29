<?php

class KitsuBridge extends BridgeAbstract
{
    const NAME = 'Kitsu Episode Updates';
    const URI = 'https://kitsu.io/api/edge/episodes?filter[mediaType]=Anime&sort=-airdate&include=media&page[limit]=20';
    const DESCRIPTION = 'Lists latest upcoming episodes';
    //const PARAMETERS = array();
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $feedContent = json_decode(getContents(self::URI), true);

        $animeList = [];

        foreach ($feedContent['included'] as $included) {
            if ($included['type'] === 'anime') {
                $animeList[(int)$included['id']] = $included['attributes'];
            }
        }

        foreach ($feedContent['data'] as $episode) {
            $item = [];

            $item['title'] = $animeList[(int)$episode['relationships']['media']['data']['id']]['canonicalTitle']
             . ': Episode ' . $episode['attributes']['number'];
            $item['content'] = $episode['attributes']['canonicalTitle'];
            if ($episode['attributes']['description']) {
                $item['content'] .= '<br/><br/>'
                 . $episode['attributes']['description'];
            }
            $item['content'] .= '<br/><br/>Airdate: ' . $episode['attributes']['airdate'];
            $item['uri'] = 'https://kitsu.io/anime/' . $animeList[(int)$episode['relationships']['media']['data']['id']]['slug']
             . '/episodes/' . $episode['attributes']['number'];
            $item['author'] = $episode['attributes']['canonicalTitle'];
            $item['timestamp'] = strtotime($episode['attributes']['createdAt']);
            $item['uid'] = $episode['id'];

            if (is_array($episode['attributes']['thumbnail'])) {
                $item['enclosures'][] = $episode['attributes']['thumbnail']['original'];
            }

            $this->items[] = $item;
        }
    }
}
