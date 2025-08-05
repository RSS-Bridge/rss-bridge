<?php

class StreamCzBridge extends BridgeAbstract
{
    const NAME = 'Stream.cz Bridge';
    const URI = 'https://www.stream.cz';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Return newest videos';
    const MAINTAINER = 'Stopka';

    const PARAMETERS = [
        [
            'url' => [
                'name' => 'url to the show',
                'required' => true,
                'exampleValue' => 'https://www.stream.cz/lajna'
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('url');

        $validUrl = '/^(https:\/\/www.stream.cz\/[a-z0-9-]+)(\/[a-z0-9-]+-\d+)?$/';
        if (!preg_match($validUrl, $url, $match)) {
            throwServerException('Invalid url');
        }

        $fixedUrl = $match[1];

        $html = getSimpleHTMLDOM($fixedUrl);

        $this->feedUri = $fixedUrl;

        $scriptElement = $html->find('body script', -1);
        if (null === $scriptElement) {
            throwServerException('Could not find metadata element on the page');
        }
        $json = extractFromDelimiters($scriptElement->innertext, 'data : ', 'logs : ');
        if (false === $json) {
            throwServerException('Could not extract metadata from the page');
        }
        $data = json_decode(trim($json, ",\t\n\r\0\x0B"), true);
        if (false === $data) {
            throwServerException('Could not parse metadata on the page');
        }

        $showData = $data['fetchable']['tag']['show']['data'];
        if (!is_array($showData)) {
            throwServerException('Show not found in metadata');
        }
        $this->feedName = $showData['name'];
        $episodes = $showData['allEpisodesConnection']['edges'];
        if (!is_array($episodes)) {
            throwServerException('Episodes not found in metadata');
        }
        foreach ($episodes as $episode) {
            if (!$episode['node']) {
                continue;
            }
            $episodeUrl = $episode['node']['urlName'];
            $imageUrlNode = reset($episode['node']['images']);
            $item = [
                'title' => $episode['node']['name'],
                'uri' => $fixedUrl . '/' . $episodeUrl,
                'content' => $imageUrlNode ? '<img src="' . $imageUrlNode['url'] . '" />' : '',
                'timestamp' => $episode['node']['publishTime']['timestamp']
            ];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        return $this->feedUri ?? parent::getURI();
    }

    public function getName()
    {
        return $this->feedName ?? parent::getName();
    }
}
