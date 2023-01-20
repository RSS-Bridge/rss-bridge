<?php

class PicartoBridge extends BridgeAbstract
{
    const NAME = 'Picarto';
    const URI = 'https://picarto.tv';
    const DESCRIPTION = 'Produces a new feed item each time a channel goes live';
    const CACHE_TIMEOUT = 300;
    const PARAMETERS = [[
            'channel' => [
                'name'      => 'Channel name',
                'type'      => 'text',
                'required'  => true,
                'title'     => 'Channel name',
                'exampleValue' => 'Wysdrem',
            ],
        ]
    ];

    public function collectData()
    {
        $channel = $this->getInput('channel');
        $data = json_decode(getContents('https://api.picarto.tv/api/v1/channel/name/' . $channel), true);
        if (!$data['online']) {
            return;
        }
        $lastLive = new \DateTime($data['last_live']);
        $this->items[] = [
            'uri' => 'https://picarto.tv/' . $channel,
            'title' => $data['name'] . ' is now online',
            'content' => sprintf('<img src="%s"/>', $data['thumbnails']['tablet']),
            'timestamp' => $lastLive->getTimestamp(),
            'uid' => 'https://picarto.tv/' . $channel . $lastLive->getTimestamp(),
        ];
    }

    public function getName()
    {
        return 'Picarto - ' . $this->getInput('channel');
    }
}
