<?php

class NyaaTorrentsBridge extends FeedExpander
{
    const MAINTAINER = 'ORelio';
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

    public function getIcon()
    {
        return self::URI . 'static/favicon.png';
    }

    public function collectData()
    {
        $this->collectExpandableDatas(
            self::URI . '?page=rss&s=id&o=desc&'
            . http_build_query([
                'f' => $this->getInput('f'),
                'c' => $this->getInput('c'),
                'q' => $this->getInput('q'),
                'u' => $this->getInput('u')
            ]),
            20
        );
    }

    protected function parseItem($newItem)
    {
        $item = parent::parseItem($newItem);

        //Convert URI from torrent file to web page
        $item['uri'] = str_replace('/download/', '/view/', $item['uri']);
        $item['uri'] = str_replace('.torrent', '', $item['uri']);

        if ($item_html = getSimpleHTMLDOMCached($item['uri'])) {
            //Retrieve full description from page contents
            $item_desc = str_get_html(
                markdownToHtml(html_entity_decode($item_html->find('#torrent-description', 0)->innertext))
            );

            //Retrieve image for thumbnail or generic logo fallback
            $item_image = $this->getURI() . 'static/img/avatar/default.png';
            foreach ($item_desc->find('img') as $img) {
                if (strpos($img->src, 'prez') === false) {
                    $item_image = $img->src;
                    break;
                }
            }

            //Add expanded fields to the current item
            $item['enclosures'] = [$item_image];
            $item['content'] = $item_desc;
        }

        return $item;
    }
}
