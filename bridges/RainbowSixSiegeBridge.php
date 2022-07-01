<?php

class RainbowSixSiegeBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Rainbow Six Siege News';
    const URI = 'https://www.ubisoft.com/en-us/game/rainbow-six/siege/news-updates';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Latest news about Rainbow Six Siege';

    // API key to call Ubisoft API, extracted from the React frontend
    const NIMBUS_API_KEY = '3u0FfSBUaTSew-2NVfAOSYWevVQHWtY9q3VM8Xx9Lto';

    public function getIcon()
    {
        return 'https://static-dm.akamaized.net/siege/prod/favicon.ico';
    }

    public function collectData()
    {
        $dlUrl = 'https://nimbus.ubisoft.com/api/v1/items?categoriesFilter=all';
        $dlUrl = $dlUrl . '&limit=6&mediaFilter=all&skip=0&startIndex=0&tags=BR-rainbow-six%20GA-siege';
        $dlUrl = $dlUrl . '&locale=en-us&fallbackLocale=en-us&environment=master';
        $jsonString = getContents($dlUrl, [
            'Authorization: ' . self::NIMBUS_API_KEY
        ]);

        $json = json_decode($jsonString, true);
        $json = $json['items'];

        // Start at index 2 to remove highlighted articles
        for ($i = 0; $i < count($json); $i++) {
            $jsonItem = $json[$i];

            $uri = 'https://www.ubisoft.com/en-us/game/rainbow-six/siege';
            $uri = $uri . $jsonItem['button']['buttonUrl'];

            $thumbnail = '<img src="' . $jsonItem['thumbnail']['url'] . '" alt="Thumbnail">';
            $content = $thumbnail . '<br />' . markdownToHtml($jsonItem['content']);

            $item = [];
            $item['uri'] = $uri;
            $item['id'] = $jsonItem['id'];
            $item['title'] = $jsonItem['title'];
            $item['content'] = $content;
            $item['timestamp'] = strtotime($jsonItem['date']);

            $this->items[] = $item;
        }
    }
}
