<?php

class KitsuBridge extends BridgeAbstract
{
    const NAME = 'Kitsu Episode Updates';
    const URI = 'https://kitsu.io';
    const DESCRIPTION = 'Lists latest upcoming episodes';
    //const PARAMETERS = array();
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        'Episodes from all shows' => [],
        'By show id' => [
            'id' => [
                'name' => 'Show id',
                'type' => 'number',
                'title' => 'Specify the id of the anime show as provided by the api',
                'exampleValue' => '43806',
                'required' => true
            ]
        ],
        'By show name' => [
            'name' => [
                'name' => 'Show name',
                'title' => 'Copy & paste the exact name from show URL',
                'exampleValue' => 'Chainsaw Man',
                'required' => true
            ]
        ],
        'By show url path' => [
            'url_path' => [
                'name' => 'Show URL path',
                'title' => 'Copy & paste the exact name from show URL',
                'exampleValue' => 'chainsaw-man',
                'required' => true
            ]
        ],
        'global' => [
            'number_of_items' => [
                'name' => 'Number of items',
                'type' => 'number',
                'title' => 'Specify the number of items in the resulting feed (max 20)',
                'exampleValue' => 20
            ]
        ]
    ];

    public function collectData()
    {
        if ($this->getInput('number_of_items') > 0 && $this->getInput('number_of_items') < 20) {
            $pageSize = $this->getInput('number_of_items');
        } else {
            $pageSize = 20;
        }

        if ($this->getInput('id') && ctype_digit($this->getInput('id'))) {
            $urlApi = self::URI . '/api/edge/episodes?filter[mediaType]=Anime&filter[media_id]=' . $this->getInput('id')
                . '&sort=-airdate&include=media&page[limit]=' . $pageSize;
        } elseif ($this->getInput('name') || $this->getInput('url_path')) {
            if ($this->getInput('url_path')) {
                $urlApiAnime = self::URI . '/api/edge/anime?filter[slug]=' . urlencode($this->getInput('url_path'));
            } else {
                $urlApiAnime = self::URI . '/api/edge/anime?filter[text]=' . urlencode($this->getInput('name'));
            }
            $animeList = json_decode(getContents($urlApiAnime), true);
            if ($animeList['meta']['count'] == 0 || !isset($animeList['data'][0]['id'])) {
                throw new \Exception('show not found');
            }
            $urlApi = self::URI . '/api/edge/episodes?filter[mediaType]=Anime&filter[media_id]=' . $animeList['data'][0]['id']
                . '&sort=-airdate&include=media&page[limit]=' . $pageSize;
        } else {
            $urlApi = self::URI . '/api/edge/episodes?filter[mediaType]=Anime&sort=-airdate&include=media&page[limit]=' . $pageSize;
        }

        $feedContent = json_decode(getContents($urlApi), true);

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

        usort($this->items, function ($item1, $item2) {
            return $item2['timestamp'] <=> $item1['timestamp'];
        });
    }
}
