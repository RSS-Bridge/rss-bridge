<?php

class OglafBridge extends FeedExpander
{
    const NAME = 'Oglaf';
    const URI = 'https://www.oglaf.com/';
    const DESCRIPTION = 'Fetch the entire comic image';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'limit (max 20)',
                'type' => 'number',
                'defaultValue' => 10,
                'required' => true,
            ]
        ]
    ];

    public function collectData()
    {
        $url = self::URI . 'feeds/rss/';
        $limit = min(20, $this->getInput('limit'));
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem($item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $comicImage = $html->find('img[id="strip"]', 0);
        $alt = $comicImage->getAttribute('alt');
        $title = $comicImage->getAttribute('title');
        $item['content'] = $comicImage . sprintf('<h3>Alt: %s</h3><h3>Title: %s</h3>', $alt, $title);

        return $item;
    }
}
