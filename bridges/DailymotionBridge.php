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
        return 'https://static1-ssl.dmcdn.net/images/neon/favicons/android-icon-36x36.png.vf806ca4ed0deed812';
    }

    public function collectData()
    {
        if ($this->queriedContext === 'By username' || $this->queriedContext === 'By playlist id') {
            $apiJson = getContents($this->getApiUrl());

            $apiData = json_decode($apiJson, true);

            $this->feedName = $this->getPlaylistTitle($this->getInput('p'));

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

        if ($this->queriedContext === 'From search results') {
            $html = getSimpleHTMLDOM($this->getURI());

            foreach ($html->find('div.media a.preview_link') as $element) {
                $item = [];

                $item['id'] = str_replace('/video/', '', strtok($element->href, '_'));
                $metadata = $this->getMetadata($item['id']);

                if (empty($metadata)) {
                    continue;
                }

                $item['uri'] = $metadata['uri'];
                $item['title'] = $metadata['title'];
                $item['timestamp'] = $metadata['timestamp'];

                $item['content'] = '<a href="'
                    . $item['uri']
                    . '"><img src="'
                    . $metadata['thumbnailUri']
                    . '" /></a><br><a href="'
                    . $item['uri']
                    . '">'
                    . $item['title']
                    . '</a>';

                $this->items[] = $item;

                if (count($this->items) >= 5) {
                    break;
                }
            }
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

    private function getMetadata($id)
    {
        $metadata = [];

        $html = getSimpleHTMLDOM(self::URI . 'video/' . $id);

        if (!$html) {
            return $metadata;
        }

        $metadata['title'] = $html->find('meta[property=og:title]', 0)->getAttribute('content');
        $metadata['timestamp'] = strtotime(
            $html->find('meta[property=video:release_date]', 0)->getAttribute('content')
        );
        $metadata['thumbnailUri'] = $html->find('meta[property=og:image]', 0)->getAttribute('content');
        $metadata['uri'] = $html->find('meta[property=og:url]', 0)->getAttribute('content');
        return $metadata;
    }

    private function getPlaylistTitle($id)
    {
        $title = '';

        $url = self::URI . 'playlist/' . $id;

        $html = getSimpleHTMLDOM($url);

        $title = $html->find('meta[property=og:title]', 0)->getAttribute('content');
        return $title;
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
        }
    }
}
