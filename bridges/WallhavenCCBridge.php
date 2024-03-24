<?php

class WallhavenCCBridge extends BridgeAbstract
{
    const MAINTAINER = 'YourName';
    const NAME = 'Wallhaven Bridge';
    const URI = 'https://wallhaven.cc/api/v1/search';
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = 'Returns wallpapers from Wallhaven based on specified criteria using the Wallhaven API.';

    const PARAMETERS = [[
        'apikey' => [
            'name' => 'API Key',
            'required' => false,
            'title' => 'Your Wallhaven API Key for accessing NSFW content.'
        ],
        'q' => [
            'name' => 'Search Query',
            'required' => false,
            'title' => 'Tags to search for. Separate tags with spaces, use + for must include, - for exclude.'
        ],
        'categories' => [
            'name' => 'Categories',
            'required' => true,
            'defaultValue' => '111'
        ],
        'purity' => [
            'name' => 'Purity',
            'required' => true,
            'defaultValue' => '100'
        ],
        'atleast' => [
            'name' => 'Minimum Resolution',
            'required' => false,
            'defaultValue' => '1920x1080'
        ],
        'sorting' => [
            'name' => 'Sorting',
            'required' => false,
            'defaultValue' => 'date_added'
        ],
        'order' => [
            'name' => 'Order',
            'required' => false,
            'defaultValue' => 'desc'
        ],
        'm' => [
            'name' => 'Max number of wallpapers',
            'required' => false,
            'defaultValue' => 24
        ]
    ]];

    public function collectData()
    {
        $params = [
            'apikey' => $this->getInput('apikey'),
            'q' => $this->getInput('q'),
            'categories' => $this->getInput('categories'),
            'purity' => $this->getInput('purity'),
            'atleast' => $this->getInput('atleast'),
            'sorting' => $this->getInput('sorting'),
            'order' => $this->getInput('order')
        ];

        $url = self::URI . '?' . http_build_query(array_filter($params));
        $headers = ['X-API-Key' => $this->getInput('apikey')];
        $context = stream_context_create(['http' => ['header' => $headers]]);
        $json = file_get_contents($url, false, $context);
        $data = json_decode($json, true);

        $max = intval($this->getInput('m'));
        $count = 0;

        foreach ($data['data'] as $wallpaper) {
            if ($count >= $max) break;

            $item = [];
            $item['uri'] = $wallpaper['url'];
            $item['title'] = "Wallpaper ID: " . $wallpaper['id'];
            $item['timestamp'] = strtotime($wallpaper['created_at']);
            $item['content'] = '<a href="' . $wallpaper['url'] . '"><img src="' . $wallpaper['thumbs']['original'] . '" /></a>';
            $item['enclosures'] = [$wallpaper['path']];

            $this->items[] = $item;
            $count++;
        }
    }

    public function getName()
    {
        return self::NAME . ' - ' . $this->getInput('q');
    }

    public function getURI()
    {
        return self::URI;
    }
}
