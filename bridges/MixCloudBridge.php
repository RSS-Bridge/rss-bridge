<?php

class MixCloudBridge extends BridgeAbstract
{
    const MAINTAINER = 'Alexis CHEMEL';
    const NAME = 'MixCloud';
    const URI = 'https://www.mixcloud.com';
    const API_URI = 'https://api.mixcloud.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns latest musics on user stream';

    const PARAMETERS = [[
        'u' => [
            'name' => 'username',
            'required' => true,
            'exampleValue' => 'DJJazzyJeff',
        ]
    ]];

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return 'MixCloud - ' . $this->getInput('u');
        }

        return parent::getName();
    }

    private static function compareDate($stream1, $stream2)
    {
        return (strtotime($stream1['timestamp']) < strtotime($stream2['timestamp']) ? 1 : -1);
    }

    public function collectData()
    {
        $user = urlencode($this->getInput('u'));
        // Get Cloudcasts
        $mixcloudUri = self::API_URI . $user . '/cloudcasts/';
        $content = getContents($mixcloudUri);
        $casts = json_decode($content)->data;

        // Get Listens
        $mixcloudUri = self::API_URI . $user . '/listens/';
        $content = getContents($mixcloudUri);
        $listens = json_decode($content)->data;

        $streams = array_merge($casts, $listens);

        foreach ($streams as $stream) {
            $item = [];

            $item['uri'] = $stream->url;
            $item['title'] = $stream->name;
            $item['content'] = '<img src="' . $stream->pictures->thumbnail . '" />';
            $item['author'] = $stream->user->name;
            $item['timestamp'] = $stream->created_time;

            $this->items[] = $item;
        }

        // Sort items by date
        usort($this->items, ['MixCloudBridge', 'compareDate']);
    }
}
