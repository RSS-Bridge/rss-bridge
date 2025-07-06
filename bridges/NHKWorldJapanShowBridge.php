<?php

class NHKWorldJapanShowBridge extends BridgeAbstract
{
    const NAME = 'NHK World-Japan Show Bridge';
    const URI = 'https://www3.nhk.or.jp';
    const DESCRIPTION = 'Returns available episodes from NHK World-Japan Shows';
    const MAINTAINER = 'TReKiE';

    const PARAMETERS = [
        [
            'show' => [
                'name' => 'Name of Show',
                'type' => 'text',
                'exampleValue' => 'ramenjapan',
                'required' => true,
                'title' => 'Enter the name of the show as it appears in the URL, e.g. "ramenjapan" for https://www3.nhk.or.jp/nhkworld/en/shows/ramenjapan/'
            ],
            'language' => [
                'name' => 'language',
                'type' => 'list',
                'title' => 'Language of the show',
                'values' => [
                    'English' => 'en',
                    'العربية' => 'ar',
                    'বাংলা' => 'bn',
                    'မြန်မာဘာသာစကား' => 'my',
                    '中文（简体）' => 'zh',
                    '中文（繁體）' => 'zt',
                    'Français' => 'fr',
                    'हिन्दी' => 'hi',
                    'Bahasa Indonesia' => 'id',
                    '코리언' => 'ko',
                    'فارسی' => 'fa',
                    'Português' => 'pt',
                    'Русский' => 'ru',
                    'Español' => 'es',
                    'Kiswahili' => 'sw',
                    'ภาษาไทย' => 'th',
                    'Türkçe' => 'tr',
                    'Українська' => 'uk',
                    'اردو' => 'ur',
                    'Tiếng Việt' => 'vi'
                ],
                'defaultValue' => 'en'
            ]
        ]
    ];

    public function getURI()
    {
        if (($this->getInput('show')) && ($this->getInput('language'))) {
            return self::URI . '/nhkworld/' . $this->getInput('language') . '/shows/' . $this->getInput('show') . '/';
        }

        return parent::getURI() . '/nhkworld/';
    }

    public function getName()
    {
        if (($this->getInput('show')) && ($this->getInput('language'))) {
            $html = getSimpleHTMLDOMCached($this->getURI());
            return html_entity_decode($html->find('meta[property="og:title"]', 0)->content, ENT_QUOTES, 'UTF-8');
        }

        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://www3.nhk.or.jp/nhkworld/common/site_images/nw_webapp.ico';
    }

    public function collectData()
    {
        $json = getContents('https://api.nhkworld.jp/nwapi/vodesdlist/v7b/program/' . $this->getInput('show') . '/' . $this->getInput('language') . '/all/all.json');
        $data = json_decode($json, true);

        if (isset($data['data']['episodes']) && is_array($data['data']['episodes'])) {
            foreach ($data['data']['episodes'] as $program) {
                $title = $program['sub_title_clean'] ?? '';
                $author = $program['title_clean'] ?? '';
                $description = $program['description'] ?? '';
                $url = $program['url'];
                $movielength = $program['movie_lengh'] ?? 'Unknown length';
                $onair = $program['onair'] ?? round(microtime(true) * 1000);
                $vod_to = $program['vod_to'] ?? round(microtime(true) * 1000);

                $thumbUrl = '';
                if (isset($program['image'])) {
                    $thumbUrl = self::URI . $program['image'];
                    ;
                    $thumbnailHtml = "<img src=\"$thumbUrl\" alt=\"Thumbnail\" /><br>";
                } else {
                    $thumbnailHtml = '';
                }

                $dt = new DateTime('@' . ($onair / 1000));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $broadcastDate = $dt->format('F j, Y');

                $description .= '<br>';
                $description .= 'Length: ' . $movielength . '<br>';
                $description .= 'Broadcast: ' . $broadcastDate . ' UTC / Available until ' . date('F j, Y', $vod_to / 1000) . '<br>';
                $description .= $thumbnailHtml;

                $item = [];
                $item['uri'] = self::URI . $url;
                $item['uid'] = self::URI . $url;
                $item['title'] = $title;
                $item['author'] = $author;
                $item['timestamp'] = $onair / 1000;
                $item['content'] = $description;

                $this->items[] = $item;
            }
        } else {
            throw new \Exception('Could not find the episodes for this show. Please create a new GitHub issue if this is unexpected.');
        }
    }
}
