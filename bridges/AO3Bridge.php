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
                // Example: F/F tag
                'exampleValue' => 'https://archiveofourown.org/tags/F*s*F/works',
            ],
            'range' => [
                'name' => 'Chapter Content',
                'title' => 'Chapter(s) to include in each work\'s feed entry',
                'defaultValue' => null,
                'type' => 'list',
                'values' => [
                    'None' => null,
                    'First' => 'first',
                    'Latest' => 'last',
                    'Entire work' => 'all',
                ],
            ],
            'unique' => [
                'name' => 'Make separate entries for new fic chapters',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Make separate entries for new fic chapters',
                'defaultValue' => 'checked',
            ],
            'limit' => self::LIMIT,
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
    private $title;

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Bookmarks':
                $this->collectList($this->getURI());
                break;
            case 'List':
                $this->collectList($this->getURI());
                break;
            case 'Work':
                $this->collectWork($this->getURI());
                break;
        }
    }

    /**
     * Feed for lists of works (e.g. recent works, search results, filtered tags,
     * bookmarks, series, collections).
     */
    private function collectList($url)
    {
        $version = 'v0.0.1';
        $headers = [
            "useragent: rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)"
        ];
        $response = getContents($url, $headers);

        $html = \str_get_html($response);
        $html = defaultLinkTo($html, self::URI);

        // Get list title. Will include page range + count in some cases
        $heading = ($html->find('#main h2', 0));
        if ($heading->find('a.tag')) {
            $heading = $heading->find('a.tag', 0);
        }
        $this->title = $heading->plaintext;

        $limit = $this->getInput('limit') ?? 3;
        $count = 0;
        foreach ($html->find('.index.group > li') as $element) {
            $item = [];

            $title = $element->find('div h4 a', 0);
            if (!isset($title)) {
                continue; // discard deleted works
            }
            $item['title'] = $title->plaintext;
            $item['uri'] = $title->href;

            $strdate = $element->find('div p.datetime', 0)->plaintext;
            $item['timestamp'] = strtotime($strdate);

            // detach from rest of page because remove() is buggy
            $element = str_get_html($element->outertext());
            $tags = $element->find('ul.required-tags', 0);
            foreach ($tags->childNodes() as $tag) {
                $item['categories'][] = html_entity_decode($tag->plaintext);
            }
            $tags->remove();
            $tags = $element->find('ul.tags', 0);
            foreach ($tags->childNodes() as $tag) {
                $item['categories'][] = html_entity_decode($tag->plaintext);
            }
            $tags->remove();

            $item['content'] = implode('', $element->childNodes());

            $chapters = $element->find('dl dd.chapters', 0);
            // bookmarked series and external works do not have a chapters count
            $chapters = (isset($chapters) ? $chapters->plaintext : 0);
            if ($this->getInput('unique')) {
                $item['uid'] = $item['uri'] . "/$strdate/$chapters";
            } else {
                $item['uid'] = $item['uri'];
            }


            // Fetch workskin of desired chapter(s) in list
            if ($this->getInput('range') && ($limit == 0 || $count++ < $limit)) {
                $url = $item['uri'];
                switch ($this->getInput('range')) {
                    case ('all'):
                        $url .= '?view_full_work=true';
                        break;
                    case ('first'):
                        break;
                    case ('last'):
                        // only way to get this is using the navigate page unfortunately
                        $url .= '/navigate';
                        $response = getContents($url, $headers);
                        $html = \str_get_html($response);
                        $html = defaultLinkTo($html, self::URI);
                        $url = $html->find('ol.index.group > li > a', -1)->href;
                        break;
                }
                $response = getContents($url, $headers);

                $html = \str_get_html($response);
                $html = defaultLinkTo($html, self::URI);
                // remove duplicate fic summary
                if ($ficsum = $html->find('#workskin > .preface > .summary', 0)) {
                    $ficsum->remove();
                }
                $item['content'] .= $html->find('#workskin', 0);
            }

            // Use predictability of download links to generate enclosures
            $wid = explode('/', $item['uri'])[4];
            foreach (['azw3', 'epub', 'mobi', 'pdf', 'html'] as $ext) {
                $item['enclosures'][] = 'https://archiveofourown.org/downloads/' . $wid . '/work.' . $ext;
            }

            $this->items[] = $item;
        }
    }

    /**
     * Feed for recent chapters of a specific work.
     */
    private function collectWork($url)
    {
        $version = 'v0.0.1';
        $headers = [
            "useragent: rss-bridge $version (https://github.com/RSS-Bridge/rss-bridge)"
        ];
        $response = getContents($url . '/navigate', $headers);

        $html = \str_get_html($response);
        $html = defaultLinkTo($html, self::URI);

        $response = getContents($url . '?view_full_work=true', $headers);

        $workhtml = \str_get_html($response);
        $workhtml = defaultLinkTo($workhtml, self::URI);

        $this->title = $html->find('h2 a', 0)->plaintext;

        $nav = $html->find('ol.index.group > li');
        for ($i = 0; $i < count($nav); $i++) {
            $item = [];

            $element = $nav[$i];
            $item['title'] = $element->find('a', 0)->plaintext;
            $item['content'] = $workhtml->find('#chapter-' . ($i + 1), 0);
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

    public function getURI()
    {
        $url = parent::getURI();
        switch ($this->queriedContext) {
            case 'Bookmarks':
                $user = $this->getInput('user');
                $url = self::URI
                    . '/users/' . $user
                    . '/bookmarks?bookmark_search[sort_column]=bookmarkable_date';
                break;
            case 'List':
                $url = $this->getInput('url');
                break;
            case 'Work':
                $url = self::URI . '/works/' . $this->getInput('id');
                break;
        }
        return $url;
    }
}
