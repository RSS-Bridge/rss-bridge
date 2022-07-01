<?php

class TebeoBridge extends FeedExpander
{
    const NAME = 'Tébéo Bridge';
    const URI = 'http://www.tebeo.bzh/';
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'Returns the newest Tébéo videos by category';
    const MAINTAINER = 'Mitsukarenai';

    const PARAMETERS = [ [
        'cat' => [
            'name' => 'Catégorie',
            'type' => 'list',
            'values' => [
                'Toutes les vidéos' => '/',
                'Actualité' => '/14-actualite',
                'Sport' => '/3-sport',
                'Culture-Loisirs' => '/5-culture-loisirs',
                'Société' => '/15-societe',
                'Langue Bretonne' => '/9-langue-bretonne'
            ]
        ]
    ]];

    public function getIcon()
    {
        return self::URI . 'images/header_logo.png';
    }

    public function collectData()
    {
        $url = self::URI . '/le-replay/' . $this->getInput('cat');
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('div[id=items_replay] div.replay') as $element) {
            $item = [];
            $item['uri'] = $element->find('a', 0)->href;
            $item['title'] = $element->find('h3', 0)->plaintext;
            $item['timestamp'] = strtotime($element->find('p.moment-format-day', 0)->plaintext);
            $item['content'] = '<a href="' . $item['uri'] . '"><img alt="" src="' . $element->find('img', 0)->src . '"></a>';
            $this->items[] = $item;
        }
    }
}
