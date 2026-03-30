<?php

class YouTubeHTMLBridge extends BridgeAbstract
{
    const NAME = 'YouTube HTML Scraper';
    const URI = 'https:///www.youtube.com';
    const DESCRIPTION = 'Scrapes YouTube without relying on the YouTube RSS endpoint';
    const MAINTAINER = 'Avvyxx';
    const CACHE_TIMEOUT = 60 * 60 * 3; // 3 hours
    const PARAMETERS = [[
        'channel' => [
            'name' => 'Channel ID',
            'required' => true,
            'exampleValue' => 'UC7YOGHUfC1Tb6E4pudI9STA',
        ],
        'hideshorts' => [
            'name' => 'Hide shorts',
            'type' => 'checkbox',
            'title' => 'Hide shorts'
        ]
    ]];

    public function collectData()
    {
        $item = []; // Create an empty item

        $item['title'] = 'Hello World!';

        $this->items[] = $item; // Add item to the list
    }
}
