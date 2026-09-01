<?php

class WallapopBridge extends BridgeAbstract
{
    const NAME = 'Wallapop Bridge';
    const URI = '';
    const DESCRIPTION = 'No description provided';
    const MAINTAINER = 'John Marston';
    const CACHE_TIMEOUT = 1;
    const PARAMETERS = [
        'By search' => [
            's' => [
                'name' => 'search',
                'exampleValue' => 'Playstation 2',
                'required' => true
            ],
            'maxPrice' => [
                'name' => 'Maximum price',
                'exampleValue' => 100,
                'type' => 'number',
                'required'=> false
            ],
            'minPrice' => [
                'name' => 'Minimum price',
                'exampleValue' => 10,
                'type' => 'number',
                'required'=> false
            ]

        ],
    ];
    public function collectData()
    {
        $search = $this->getInput('s');
        $maxPrice = $this->getInput('maxPrice');
        $minPrice = $this->getInput('minPrice');

        $this->feedName = 'Search: ' . $search;

        $url = 'https://api.wallapop.com/api/v3/search/section?keywords=' . urlencode($search) .
            '&source=search_box&order_by=newest&search_country=ES&section_type=vector_search_results';

        $headers = [
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:154.0) Gecko/20100101 Firefox/154.0',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: es,en-US;q=0.9,en;q=0.8',
            'Referer: https://es.wallapop.com/',
            'x-appversion: 826590',
            'deviceos: 0',
            'x-deviceos: 0',
        ];

        $response = getContents($url, $headers);
        $json = json_decode($response, true);

        $items = $json['data']['section']['items'] ?? [];

        foreach ($items as $entry) {
            $itemPrice = $entry['price']['amount'];

            if ($maxPrice !== null && $itemPrice > $maxPrice) continue;
            if ($minPrice !== null && $itemPrice < $minPrice) continue;

            $item = [];

            $title = $entry['title'] ?? $search;

            $date = intdiv($entry['created_at'], 1000);
            $imageUrl = $entry['images'][0]['urls']['small'];
            $fullDesc = "<img src=\"{$imageUrl}\"><br>" . $entry['description'];

            $item['title'] = "$title - $itemPrice EUR";
            $item['description'] = $item['summary'] = $item['content'] = $fullDesc;
            $item['timestamp'] = $item['published'] = $date;
            $item['link'] = $item['uri'] = "https://es.wallapop.com/item/{$entry['web_slug']}";
            $item['enclosures'] = [$entry['images'][0]['urls']['medium'] ?? $entry['images'][0]['url'] ?? ''];

            $this->items[] = $item;
        }
    }
    public function getName()
    {
        return self::NAME . ' ' . $this->getInput('s');
    }
}
