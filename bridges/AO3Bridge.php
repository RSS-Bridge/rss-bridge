<?php

class AO3Bridge extends BridgeAbstract
{
    const NAME = 'AO3';
    const URI = 'https://archiveofourown.org/';
    const CACHE_TIMEOUT = 1800;
    const DESCRIPTION = 'Returns works or chapters from Archive of Our Own';
    const MAINTAINER = 'Obsidienne';
    const PARAMETERS = [
        'List' => [
            'url' => [
                'name' => 'url',
                'required' => true,
                // Example: F/F tag, complete works only
                'exampleValue' => 'https://archiveofourown.org/works?work_search[complete]=T&tag_id=F*s*F',
            ],
        ],
        'Bookmarks' => [
            'user' => [
                'name' => 'user',
                'required' => true,
                // Example: Nyaaru's bookmarks
                'exampleValue' => 'Nyaaru',
            ],
        ],
        'Work' => [
            'id' => [
                'name' => 'id',
                'required' => true,
                // Example: latest chapters from A Better Past by LysSerris
                'exampleValue' => '18181853',
            ],
        ]
    ];

    // Feed for lists of works (e.g. recent works, search results, filtered tags,
    // bookmarks, series, collections).
    private function collectList($url)
    {
        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, self::URI);

        foreach ($html->find('.index.group > li') as $element) {
            $item = [];

            $title = $element->find('div h4 a', 0);
            if (!isset($title)) {
                continue; // discard deleted works
            }
            $item['title'] = $title->plaintext;
            $item['content'] = $element;
            $item['uri'] = $title->href;

            $strdate = $element->find('div p.datetime', 0)->plaintext;
            $item['timestamp'] = strtotime($strdate);

            $chapters = $element->find('dl dd.chapters', 0);
            // bookmarked series and external works do not have a chapters count
            $chapters = (isset($chapters) ? $chapters->plaintext : 0);
            $item['uid'] = $item['uri'] . "/$strdate/$chapters";

            $this->items[] = $item;
        }
    }

    // Feed for recent chapters of a specific work.
    private function collectWork($id)
    {
        $url = self::URI . "/works/$id/navigate";
        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, self::URI);

        $this->title = $html->find('h2 a', 0)->plaintext;

        foreach ($html->find('ol.index.group > li') as $element) {
            $item = [];

            $item['title'] = $element->find('a', 0)->plaintext;
            $item['content'] = $element;
            $item['uri'] = $element->find('a', 0)->href;

            $strdate = $element->find('span.datetime', 0)->plaintext;
            $strdate = str_replace('(', '', $strdate);
            $strdate = str_replace(')', '', $strdate);
            $item['timestamp'] = strtotime($strdate);

            $item['uid'] = $item['uri'] . "/$strdate";

            $this->items[] = $item;
        }

        $this->items = array_reverse($this->items);
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Bookmarks':
                $user = $this->getInput('user');
                $this->title = $user;
                $url = self::URI
                    . '/users/' . $user
                    . '/bookmarks?bookmark_search[sort_column]=bookmarkable_date';
                return $this->collectList($url);
            case 'List':
                return $this->collectList(
                    $this->getInput('url')
                );
            case 'Work':
                return $this->collectWork(
                    $this->getInput('id')
                );
        }
    }

    public function getName()
    {
        $name = parent::getName() . " $this->queriedContext";
        if (isset($this->title)) {
            $name .= " - $this->title";
        }
        return $name;
    }

    public function getIcon()
    {
        return self::URI . '/favicon.ico';
    }
}
