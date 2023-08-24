<?php

class Arte7Bridge extends BridgeAbstract
{
    const NAME = 'Arte +7';
    const URI = 'https://www.arte.tv/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns newest videos from ARTE +7';

    const API_TOKEN = 'Nzc1Yjc1ZjJkYjk1NWFhN2I2MWEwMmRlMzAzNjI5NmU3NWU3ODg4ODJjOWMxNTMxYzEzZGRjYjg2ZGE4MmIwOA';

    const PARAMETERS = [
        'global' => [
            'sort_by' => [
                'type' => 'list',
                'name' => 'Sort by',
                'required' => false,
                'defaultValue' => null,
                'values' => [
                    'Default' => null,
                    'Video rights start date' => 'videoRightsBegin',
                    'Video rights end date' => 'videoRightsEnd',
                    'Brodcast date' => 'broadcastBegin',
                    'Creation date' => 'creationDate',
                    'Last modified' => 'lastModified',
                    'Number of views' => 'views',
                    'Number of views per period' => 'viewsPeriod',
                    'Available screens' => 'availableScreens',
                    'Episode' => 'episode'
                ],
            ],
            'sort_direction' => [
                'type' => 'list',
                'name' => 'Sort direction',
                'required' => false,
                'defaultValue' => 'DESC',
                'values' => [
                    'Ascending' => 'ASC',
                    'Descending' => 'DESC'
                ],
            ],
            'exclude_trailers' => [
                'name' => 'Exclude trailers',
                'type' => 'checkbox',
                'required' => false,
                'defaultValue' => false
            ],
        ],
        'Category' => [
            'lang' => [
                'type' => 'list',
                'name' => 'Language',
                'values' => [
                    'Français' => 'fr',
                    'Deutsch' => 'de',
                    'English' => 'en',
                    'Español' => 'es',
                    'Polski' => 'pl',
                    'Italiano' => 'it'
                ],
            ],
            'cat' => [
                'type' => 'list',
                'name' => 'Category',
                'values' => [
                    'All videos' => null,
                    'News & society' => 'ACT',
                    'Series & fiction' => 'SER',
                    'Cinema' => 'CIN',
                    'Culture' => 'ARS',
                    'Culture pop' => 'CPO',
                    'Discovery' => 'DEC',
                    'History' => 'HIST',
                    'Science' => 'SCI',
                    'Other' => 'AUT'
                ]
            ],
        ],
        'Collection' => [
            'lang' => [
                'type' => 'list',
                'name' => 'Language',
                'values' => [
                    'Français' => 'fr',
                    'Deutsch' => 'de',
                    'English' => 'en',
                    'Español' => 'es',
                    'Polski' => 'pl',
                    'Italiano' => 'it'
                ]
            ],
            'col' => [
                'name' => 'Collection id',
                'required' => true,
                'title' => 'ex. RC-014095 pour https://www.arte.tv/de/videos/RC-014095/blow-up/',
                'exampleValue'  => 'RC-014095'
            ]
        ]
    ];

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Category':
                $category = $this->getInput('cat');
                $collectionId = null;
                break;
            case 'Collection':
                $collectionId = $this->getInput('col');
                $category = null;
                break;
        }

        $lang = $this->getInput('lang');
        $sort_by = $this->getInput('sort_by');
        $sort_direction = $this->getInput('sort_direction') == 'ASC' ? '' : '-';

        $url = 'https://api.arte.tv/api/opa/v3/videos?limit=15&language='
            . $lang
            . ($sort_by != null ? '&sort=' . $sort_direction . $sort_by : '')
            . ($category != null ? '&category.code=' . $category : '')
            . ($collectionId != null ? '&collections.collectionId=' . $collectionId : '');

        $header = [
            'Authorization: Bearer ' . self::API_TOKEN
        ];

        $input = getContents($url, $header);
        $input_json = json_decode($input, true);

        foreach ($input_json['videos'] as $element) {
            if ($this->getInput('exclude_trailers') && $element['platform'] == 'EXTRAIT') {
                continue;
            }

            $durationSeconds = $element['durationSeconds'];

            $item = [];
            $item['uri'] = $element['url'];
            $item['id'] = $element['id'];

            $item['timestamp'] = strtotime($element['videoRightsBegin']);
            $item['title'] = $element['title'];

            if (!empty($element['subtitle'])) {
                $item['title'] = $element['title'] . ' | ' . $element['subtitle'];
            }

            $durationMinutes = round((int)$durationSeconds / 60);
            $item['content'] = $element['teaserText']
            . '<br><br>'
            . $durationMinutes
            . 'min<br><a href="'
            . $item['uri']
            . '"><img src="'
            . $element['mainImage']['url']
            . '" /></a>';

            $this->items[] = $item;
        }
    }
}
