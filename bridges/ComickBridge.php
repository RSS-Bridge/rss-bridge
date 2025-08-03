<?php

class ComickBridge extends BridgeAbstract
{
    const MAINTAINER = 'phantop';
    const NAME = 'Comick';
    const URI = 'https://comick.io/';
    const DESCRIPTION = 'Returns the latest chapters for a manga on comick.io.';
    const PARAMETERS = [[
        'slug' => [
            'name' => 'Manga Slug',
            'type' => 'text',
            'required' => true,
            'title' => 'The part of the URL after /comic/',
            'exampleValue' => '00-kusuriya-no-hitorigoto-maomao-no-koukyuu-nazotoki-techou'
        ],
        'lang' => [
            'name' => 'Language',
            'type' => 'list',
            'title' => 'Language for comic (list is # of comics, descending)',
            'values' => [
                'English' => 'en',
                'Brazilian Portuguese' => 'pt-br',
                'Spanish Latin American' => 'es-la',
                'Russian' => 'ru',
                'Vietnamese' => 'vi',
                'French' => 'fr',
                'Polish' => 'pl',
                'Indonesian' => 'id',
                'Turkish' => 'tr',
                'Italian' => 'it',
                'Spanish; Castilian' => 'es',
                'Ukrainian' => 'uk',
                'Arabic' => 'ar',
                'Hong Kong (Traditional Chinese)' => 'zh-hk',
                'Hungarian' => 'hu',
                'Chinese' => 'zh',
                'German' => 'de',
                'Korean' => 'ko',
                'Thai' => 'th',
                'Catalan; Valencian' => 'ca',
                'Bulgarian' => 'bg',
                'Persian' => 'fa',
                'Romanian, Moldavian, Moldovan' => 'ro',
                'Czech' => 'cs',
                'Mongolian' => 'mn',
                'Portuguese' => 'pt',
                'Hebrew (modern)' => 'he',
                'Hindi' => 'hi',
                'Filipino/Tagalog' => 'tl',
                'Finnish' => 'fi',
                'Malay' => 'ms',
                'Basque' => 'eu',
                'Kazakh' => 'kk',
                'Serbian' => 'sr',
                'Burmese' => 'my',
                'Japanese' => 'ja',
                'Greek, Modern' => 'el',
                'Dutch' => 'nl',
                'Bengali' => 'bn',
                'Uzbek' => 'uz',
                'Esperanto' => 'eo',
                'Lithuanian' => 'lt',
                'Georgian' => 'ka',
                'Danish' => 'da',
                'Tamil' => 'ta',
                'Swedish' => 'sv',
                'Belarusian' => 'be',
                'Chuvash' => 'cv',
                'Croatian' => 'hr',
                'Latin' => 'la',
                'Nepali' => 'ne',
                'Urdu' => 'ur',
                'Galician' => 'gl',
                'Norwegian' => 'no',
                'Albanian' => 'sq',
                'Irish' => 'ga',
                'Javanese' => 'jv',
                'Telugu' => 'te',
                'Slovene' => 'sl',
                'Estonian' => 'et',
                'Azerbaijani' => 'az',
                'Slovak' => 'sk',
                'Afrikaans' => 'af',
                'Latvian' => 'lv',
            ],
            'defaultValue' => 'en'
        ],
        'fetch' => [
            'name' => 'Fetch chapter page images',
            'type' => 'list',
            'title' => 'Places chapter images in feed contents. Entries will consume more bandwidth.',
            'defaultValue' => 'c',
            'values' => [
                'None' => 'n',
                'Content' => 'c',
                'Enclosure' => 'e'
            ]
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'title' => 'Maximum number of chapters to return',
            'defaultValue' => 10
        ]
    ]];

    private $title;

    private function getComick($url)
    {
        $API = 'https://api.comick.fun';

        // Need a non-cURL UA, otherwise we get Cloudflare 403'd
        $opts = [
            CURLOPT_USERAGENT => 'rss-bridge (https://github.com/RSS-Bridge/rss-bridge)'
        ];
        $content = getContents("$API/$url", [], $opts);
        return json_decode($content, true);
    }

    public function collectData()
    {
        $slug = $this->getInput('slug');
        $lang = $this->getInput('lang');
        $limit = $this->getInput('limit');

        $manga = $this->getComick("comic/$slug");
        $hid = $manga['comic']['hid'];
        $this->title = $manga['comic']['title'];
        $manga = $this->getComick("comic/$hid/chapters?lang=$lang&limit=$limit");

        foreach ($manga['chapters'] as $chapter) {
            $hid = $chapter['hid'];
            $item['author'] = implode(', ', $chapter['group_name']);
            $item['timestamp'] = strtotime($chapter['created_at']);
            $item['uri'] = $this->getURI() . '/' . $hid;

            $item['title'] = '';
            if ($chapter['vol']) {
                $item['title'] .= ' Vol. ' . $chapter['vol'];
            }
            if ($chapter['chap']) {
                $item['title'] .= ' Ch. ' . $chapter['chap'];
            }
            if ($chapter['title']) {
                $item['title'] .= ' - ' . $chapter['title'];
            }


            if ($this->getInput('fetch') != 'n') {
                $chapter = $this->getComick("chapter/$hid");
                if (isset($chapter['chapter']['md_images'])) {
                    $item['content'] = '';
                    foreach ($chapter['chapter']['md_images'] as $image) {
                        $img = 'https://meo.comick.pictures/' . $image['b2key'];
                        if ($this->getInput('fetch') == 'c') {
                            $item['content'] .= '<img src="' . $img . '" />';
                        }
                        if ($this->getInput('fetch') == 'e') {
                            $item['enclosures'][] = $img;
                        }
                    }
                }
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if ($this->title) {
            return parent::getName() . ' - ' . $this->title;
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->getInput('slug')) {
            return self::URI . 'comic/' . $this->getInput('slug');
        }
        return parent::getURI();
    }
}
