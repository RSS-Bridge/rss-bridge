<?php

class ScarletComicBridge extends FeedExpander
{
    const NAME = 'Scarlet Comic';
    const URI = 'https://www.sandraandwoo.com';
    const DESCRIPTION = 'Fetch the entire comic page';
    const MAINTAINER = 'Cyberax';
    const PARAMETERS = [
        [
            'limit' => [
                'name' => 'limit (max 5)',
                'type' => 'number',
                'defaultValue' => 5,
                'required' => true,
            ]
        ]
    ];

    public function collectData()
    {
        $url = self::URI . '/scarlet/feed';
        $limit = min(5, $this->getInput('limit'));
        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem($item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
	$comicImage = $html->find('div[id="spliced-comic"]', 0);	
        $item['content'] = $comicImage;

        return $item;
    }
}
