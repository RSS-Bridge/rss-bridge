<?php

class NyaaTorrentsBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio & Jisagi';
    const NAME = 'NyaaTorrents';
    const URI = 'https://nyaa.si/';
    const DESCRIPTION = 'Returns the newest torrents, with optional search criteria.';
    const PARAMETERS = [
        [
            'f' => [
                'name' => 'Filter',
                'type' => 'list',
                'values' => [
                    'No filter' => '0',
                    'No remakes' => '1',
                    'Trusted only' => '2'
                ]
            ],
            'c' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All categories' => '0_0',
                    'Anime' => '1_0',
                    'Anime - AMV' => '1_1',
                    'Anime - English' => '1_2',
                    'Anime - Non-English' => '1_3',
                    'Anime - Raw' => '1_4',
                    'Audio' => '2_0',
                    'Audio - Lossless' => '2_1',
                    'Audio - Lossy' => '2_2',
                    'Literature' => '3_0',
                    'Literature - English' => '3_1',
                    'Literature - Non-English' => '3_2',
                    'Literature - Raw' => '3_3',
                    'Live Action' => '4_0',
                    'Live Action - English' => '4_1',
                    'Live Action - Idol/PV' => '4_2',
                    'Live Action - Non-English' => '4_3',
                    'Live Action - Raw' => '4_4',
                    'Pictures' => '5_0',
                    'Pictures - Graphics' => '5_1',
                    'Pictures - Photos' => '5_2',
                    'Software' => '6_0',
                    'Software - Apps' => '6_1',
                    'Software - Games' => '6_2',
                ]
            ],
            'q' => [
                'name' => 'Keyword',
                'description' => 'Keyword(s)',
                'type' => 'text'
            ],
            'u' => [
                'name' => 'User',
                'description' => 'User',
                'type' => 'text'
            ]
        ]
    ];

    public function collectData()
    {
        $feedParser = new FeedParser();
        $feed = $feedParser->parseFeed(getContents($this->getURI()));

        foreach ($feed['items'] as $item) {
            $item['enclosures'] = [$item['uri']];
            $item['uri'] = str_replace('.torrent', '', $item['uri']);
            $item['uri'] = str_replace('/download/', '/view/', $item['uri']);
            $item['id'] = str_replace('https://nyaa.si/view/', '', $item['uri']);
            $dom = getSimpleHTMLDOMCached($item['uri']);
            if ($dom) {
                $description = $dom->find('#torrent-description', 0)->innertext ?? '';
                $item['content'] = markdownToHtml(html_entity_decode($description));

                $magnet = $dom->find('div.panel-footer.clearfix > a', 1)->href;
                // can't put raw magnet link in enclosure, this gives information on
                // magnet contents and works a way to sent magnet value
                $magnet = 'https://torrent.parts/#' . html_entity_decode($magnet);
                array_push($item['enclosures'], $magnet);
            }
            $this->items[] = $item;
            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    public function getName()
    {
        $name = parent::getName();
        $name .= $this->getInput('u') ? ' - ' . $this->getInput('u') : '';
        $name .= $this->getInput('q') ? ' - ' . $this->getInput('q') : '';
        $name .= $this->getInput('c') ? ' (' . $this->getKey('c') . ')' : '';
        return $name;
    }

    public function getIcon()
    {
        return self::URI . 'static/favicon.png';
    }

    public function getURI()
    {
        $params = [
            'f' => $this->getInput('f'),
            'c' => $this->getInput('c'),
            'q' => $this->getInput('q'),
            'u' => $this->getInput('u'),
        ];
        return self::URI . '?page=rss&s=id&o=desc&' . http_build_query($params);
    }
}
