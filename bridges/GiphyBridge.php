<?php

class GiphyBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'Giphy Bridge';
    const URI = 'https://giphy.com/';
    const CACHE_TIMEOUT = 60 * 60 * 8; // 8h
    const DESCRIPTION = 'Bridge for giphy.com';

    const PARAMETERS = [ [
        's' => [
            'name' => 'search tag',
            'exampleValue' => 'bird',
            'required' => true
        ],
        'noGif' => [
            'name' => 'Without gifs',
            'type' => 'checkbox',
            'title' => 'Exclude gifs from the results'
        ],
        'noStick' => [
            'name' => 'Without stickers',
            'type' => 'checkbox',
            'title' => 'Exclude stickers from the results'
        ],
        'n' => [
            'name' => 'max number of returned items (max 50)',
            'type' => 'number',
            'exampleValue' => 3,
        ]
    ]];

    public function getName()
    {
        if (!is_null($this->getInput('s'))) {
            return $this->getInput('s') . ' - ' . parent::getName();
        }

        return parent::getName();
    }

    protected function getGiphyItems($entries)
    {
        foreach ($entries as $entry) {
            $createdAt = new \DateTime($entry->import_datetime);

            $this->items[] = [
                'id'        => $entry->id,
                'uri'       => $entry->url,
                'author'    => $entry->username,
                'timestamp' => $createdAt->format('U'),
                'title'     => $entry->title,
                'content'   => <<<HTML
<a href="{$entry->url}">
<img
	loading="lazy"
	src="{$entry->images->downsized->url}"
	width="{$entry->images->downsized->width}"
	height="{$entry->images->downsized->height}" />
</a>
HTML
            ];
        }
    }

    public function collectData()
    {
        /**
         * This uses Giphy's own undocumented public prod api key,
         * which should not have any rate limiting.
         * There is a documented public beta api key (dc6zaTOxFJmzC),
         * but it has severe rate limiting.
         *
         * https://giphy.api-docs.io/1.0/welcome/access-and-api-keys
         * https://developers.giphy.com/branch/master/docs/api/endpoint/#search
         */
        $apiKey = 'Gc7131jiJuvI7IdN0HZ1D7nh0ow5BU6g';
        $bundle = 'low_bandwidth';
        $limit = min($this->getInput('n') ?: 10, 50);
        $endpoints = [];
        if (empty($this->getInput('noGif'))) {
            $endpoints[] = 'gifs';
        }
        if (empty($this->getInput('noStick'))) {
            $endpoints[] = 'stickers';
        }

        foreach ($endpoints as $endpoint) {
            $uri = sprintf(
                'https://api.giphy.com/v1/%s/search?q=%s&limit=%s&bundle=%s&api_key=%s',
                $endpoint,
                rawurlencode($this->getInput('s')),
                $limit,
                $bundle,
                $apiKey
            );

            $result = json_decode(getContents($uri));

            $this->getGiphyItems($result->data);
        }

        usort($this->items, function ($a, $b) {
            return $a['timestamp'] < $b['timestamp'];
        });
    }
}
