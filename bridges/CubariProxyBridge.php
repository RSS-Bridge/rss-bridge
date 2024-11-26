<?php

class CubariProxyBridge extends BridgeAbstract
{
    const NAME = 'Cubari Proxy';
    const MAINTAINER = 'phantop';
    const URI = 'https://cubari.moe';
    const DESCRIPTION = 'Returns chapters from Cubari.';
    const PARAMETERS = [[
        'service' => [
            'name' => 'Content service',
            'type' => 'list',
            'defaultValue' => 'mangadex',
            'values' => [
                'MangAventure' => 'mangadventure',
                'MangaDex' => 'mangadex',
                'MangaKatana' => 'mangakatana',
                'MangaSee' => 'mangasee',
            ]
        ],
        'series' => [
            'name' => 'Series ID/Name',
            'exampleValue' => '8c1d7d0c-e0b7-4170-941d-29f652c3c19d', # KnH
            'required' => true,
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
        'limit' => self::LIMIT
    ]];

    private $title;

    public function collectData()
    {
        $limit = $this->getInput('limit') ?? 10;

        $url = parent::getURI() . '/read/api/' . $this->getInput('service') . '/series/' . $this->getInput('series');
        $json = Json::decode(getContents($url));
        $this->title = $json['title'];

        $chapters = $json['chapters'];
        krsort($chapters);

        $count = 0;
        foreach ($chapters as $number => $element) {
            $item = [];
            $item['uri'] = $this->getURI() . '/' . $number;

            if ($element['title']) {
                $item['title'] = $number . ' - ' . $element['title'];
            } else {
                $item['title'] = 'Volume ' . $element['volume'] . ' Chapter ' . $number;
            }

            $group = '1';
            if (isset($element['release_date'])) {
                $dates = $element['release_date'];
                $date = max($dates);
                $item['timestamp'] = $date;
                $group = array_keys($dates, $date)[0];
            }
            $page = $element['groups'][$group];
            $item['author'] = $json['groups'][$group];
            $api = parent::getURI() . $page;
            $item['uid'] = $page;
            $item['comments'] = $api;

            if ($this->getInput('fetch') != 'n') {
                $pages = [];
                try {
                    $jsonp = getContents($api);
                    $pages = Json::decode($jsonp);
                } catch (HttpException $e) {
                    // allow error 500, as it's effectively a 429
                    if ($e->getCode() != 500) {
                        throw $e;
                    }
                }
                if ($this->getInput('fetch') == 'e') {
                    $item['enclosures'] = $pages;
                }
                if ($this->getInput('fetch') == 'c') {
                    $item['content'] = '';
                    foreach ($pages as $img) {
                        $item['content'] .= '<img src="' . $img . '"/>';
                    }
                }
            }

            if ($count++ == $limit) {
                break;
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->title)) {
            $name .= ' - ' . $this->title;
        }
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI();
        if ($this->getInput('service')) {
            $uri .= '/read/' . $this->getInput('service') . '/' . $this->getInput('series');
        }
        return $uri;
    }

    public function getFavicon()
    {
        return parent::getURI() . '/static/favicon.ico';
    }
}
