<?php

class RutubeBridge extends BridgeAbstract
{
    const NAME = 'Rutube';
    const URI = 'https://rutube.ru';
    const MAINTAINER = 'em92';
    const DESCRIPTION = 'Выводит ленту видео';

    const PARAMETERS = [
        'По каналу' => [
            'c' => [
                'name' => 'ИД канала',
                'exampleValue' => 1342940,  // Мятежник Джек
                'type' => 'number',
                'required' => true
            ],
        ],
        'По плейлисту' => [
            'p' => [
                'name' => 'ИД плейлиста',
                'exampleValue' => 83641,  // QRUSH
                'type' => 'number',
                'required' => true
            ],
        ],
        'По результатам поиска' => [
            's' => [
                'name' => 'Запрос',
                'exampleValue' => 'SUREN',
                'required' => true,
            ]
        ]
    ];

    protected $title;

    public function getURI()
    {
        if ($this->getInput('c')) {
            return self::URI . '/channel/' . strval($this->getInput('c')) . '/videos/';
        } elseif ($this->getInput('p')) {
            return self::URI . '/plst/' . strval($this->getInput('p')) . '/';
        } elseif ($this->getInput('s')) {
            return self::URI . '/search/?suggest=1&query=' . strval($this->getInput('s'));
        } else {
            return parent::getURI();
        }
    }

    public function getIcon()
    {
        return 'https://static.rutube.ru/static/favicon.ico';
    }

    public function getName()
    {
        if (is_null($this->title)) {
            return parent::getName();
        } else {
            return $this->title . ' - ' . parent::getName();
        }
    }

    private function getJSONData($html)
    {
        $jsonDataRegex = '/window.reduxState = (.*);/';
        preg_match($jsonDataRegex, $html, $matches) or throwServerException('Could not find reduxState');
        $map = [
            '\x26' => '&',
            '\x3c' => '<',
            '\x3d' => '=',
            '\x3e' => '>',
            '\x3f' => '?',
        ];
        $jsonString = str_replace(array_keys($map), array_values($map), $matches[1]);
        return json_decode($jsonString, false);
    }

    private function getVideosFromReduxState()
    {
        $link = $this->getURI();

        $html = getContents($link);
        $reduxState = $this->getJSONData($html);
        $videos = [];
        if ($this->getInput('c')) {
            $videosMethod = 'videos(' . $this->getInput('c') . ')';
            $channelInfoMethod = 'channelInfo({"userChannelId":' . $this->getInput('c') . '})';
            $videos = $reduxState->api->queries->$videosMethod->data->results;
            $this->title = $reduxState->api->queries->$channelInfoMethod->data->name;
        } elseif ($this->getInput('p')) {
            $playListVideosMethod = 'getPlaylistVideos(' . $this->getInput('p') . ')';
            $videos = $reduxState->api->queries->$playListVideosMethod->data->results;
            $playListMethod = 'getPlaylist(' . $this->getInput('p') . ')';
            $this->title = $reduxState->api->queries->$playListMethod->data->title;
        } elseif ($this->getInput('s')) {
            $this->title = 'Поиск ' . $this->getInput('s');
        }

        return $videos;
    }

    private function getVideosFromSearchAPI()
    {
        $contents = getContents(self::URI . '/api/search/video/?suggest=1&client=wdp&query=' . $this->getInput('s'));
        $json = json_decode($contents);
        return $json->results;
    }

    public function collectData()
    {
        if ($this->getInput('c') || $this->getInput('p')) {
            $videos = $this->getVideosFromReduxState();
        } else {
            $videos = $this->getVideosFromSearchAPI();
        }

        foreach ($videos as $video) {
            $item = [];
            $item['title'] = $video->title;
            $item['uri'] = $video->video_url;
            $content = '<a href="' . $video->video_url . '">';
            $content .= '<img src="' . $video->thumbnail_url . '" />';
            $content .= '</a><br/>';
            $content .= nl2br(
                // Converting links in plaintext
                // Copied from https://stackoverflow.com/a/12590772
                preg_replace(
                    '$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i',
                    ' <a href="$1" target="_blank">$1</a> ',
                    $video->description . ' '
                )
            );
            $item['timestamp'] = $video->publication_ts;
            $item['author'] = $video->author->name;
            $item['content'] = $content;

            $this->items[] = $item;
        }
    }
}
