<?php

class DailymotionBridge extends BridgeAbstract
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Dailymotion Bridge';
    const URI = 'https://www.dailymotion.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the 5 newest videos by username/playlist or search';

    const PARAMETERS = [
        'By username' => [
            'u' => [
                'name' => 'username',
                'required' => true,
                'exampleValue' => 'moviepilot',
            ]
        ],
        'By playlist id' => [
            'p' => [
                'name' => 'playlist id',
                'required' => true,
                'exampleValue' => 'x6xyc6',
            ]
        ],
        'From search results' => [
            's' => [
                'name' => 'Search keyword',
                'required' => true,
                'exampleValue' => 'matrix',
            ],
            'pa' => [
                'name' => 'Page',
                'type' => 'number',
                'defaultValue' => 1,
            ]
        ]
    ];

    private $feedName = '';

    private $apiUrl = 'https://api.dailymotion.com';
    private $apiFields = 'created_time,description,id,owner.screenname,tags,thumbnail_url,title,url';

    public function getIcon()
    {
        return 'https://static1.dmcdn.net/neon-user-ssr/prod/favicons/apple-icon-60x60.831b96ed0a8eca7f6539.png';
    }

    public function collectData()
    {
        $apiJson = getContents($this->getApiUrl());
        $apiData = json_decode($apiJson, true);

        if ($this->queriedContext === 'By playlist id') {
            $this->feedName = $this->getPlaylistTitle($this->getInput('p'));
        }

        foreach ($apiData['list'] as $apiItem) {
            $item = [];

            $item['uri'] = $apiItem['url'];
            $item['uid'] = $apiItem['id'];
            $item['title'] = $apiItem['title'];
            $item['timestamp'] = $apiItem['created_time'];
            $item['author'] = $apiItem['owner.screenname'];
            $item['content'] = '<p><a href="' . $apiItem['url'] . '">
				<img src="' . $apiItem['thumbnail_url'] . '"></a></p><p>' . $apiItem['description'] . '</p>';
            $item['categories'] = $apiItem['tags'];
            $item['enclosures'][] = $apiItem['thumbnail_url'];

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'By username':
                $specific = $this->getInput('u');
                break;
            case 'By playlist id':
                $specific = strtok($this->getInput('p'), '_');

                if ($this->feedName) {
                    $specific = $this->feedName;
                }

                break;
            case 'From search results':
                $specific = $this->getInput('s');
                break;
            default:
                return parent::getName();
        }

        return $specific . ' : Dailymotion';
    }

    public function getURI()
    {
        $uri = self::URI;

        switch ($this->queriedContext) {
            case 'By username':
                $uri .= 'user/' . urlencode($this->getInput('u'));
                break;
            case 'By playlist id':
                $uri .= 'playlist/' . urlencode(strtok($this->getInput('p'), '_'));
                break;
            case 'From search results':
                $uri .= 'search/' . urlencode($this->getInput('s'));

                if (!is_null($this->getInput('pa'))) {
                    $pa = $this->getInput('pa');

                    if ($this->getInput('pa') < 1) {
                        $pa = 1;
                    }

                    $uri .= '/' . $pa;
                }
                break;
            default:
                return parent::getURI();
        }
        return $uri;
    }

    private function getPlaylistTitle($id)
    {
        $apiJson = getContents($this->apiUrl . '/playlist/' . $this->getInput('p'));
        $apiData = json_decode($apiJson, true);
        return $apiData['name'];
    }

    private function getApiUrl()
    {
        switch ($this->queriedContext) {
            case 'By username':
                return $this->apiUrl . '/user/' . $this->getInput('u')
                    . '/videos?fields=' . urlencode($this->apiFields) . '&availability=1&sort=recent&limit=5';
                break;
            case 'By playlist id':
                return $this->apiUrl . '/playlist/' . $this->getInput('p')
                    . '/videos?fields=' . urlencode($this->apiFields) . '&limit=5';
                break;
            case 'From search results':
                return $this->apiUrl . '/videos?search=' . $this->getInput('s') . '&fields=' . urlencode($this->apiFields) . '&limit=5';
                break;
        }
    }
}
