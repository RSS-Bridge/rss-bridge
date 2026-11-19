<?php

class AO3Bridge extends BridgeAbstract
{
    const NAME = 'AO3';
    const URI = 'https://archiveofourown.org/';
    const CACHE_TIMEOUT = 1800;
    const DESCRIPTION = 'Returns works or chapters from Archive of Our Own';
    const MAINTAINER = 'Phantop';
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
    // AO3 asked us to identify as a bot rather than imitate a browser (#3188)
    private const HEADERS = [
        'User-Agent: rss-bridge bot (https://github.com/RSS-Bridge/rss-bridge)',
        // Empty values make curl drop curl-impersonate's browser-only headers
        'Sec-Ch-Ua:',
        'Sec-Ch-Ua-Mobile:',
        'Sec-Ch-Ua-Platform:',
        'Sec-Fetch-Dest:',
        'Sec-Fetch-Mode:',
        'Sec-Fetch-Site:',
        'Sec-Fetch-User:',
        'Upgrade-Insecure-Requests:',
        'Priority:',
    ];
    private $title;

    private function getContentsWithFallback($url, $headers)
    {
        $mirrors = [
            'https://insecure.archiveofourown.org/',
            'https://staging.archiveofourown.org/',
            'https://archive.transformativeworks.org',
            'https://archiveofourown.gay',
            'https://secure.ao3.org/',
            'https://ao3-mirror.cc/',
            'https://dry-water-e911.ao365.workers.dev/',
            'https://mute-disk-cf7e.ao33.workers.dev/',
        ];
        $urls = [$url];
        foreach ($mirrors as $mirror) {
            $urls[] = preg_replace(
                '#^https?://archiveofourown\.org#',
                $mirror,
                $url
            );
        }

        $lastException = null;
        foreach ($urls as $url) {
            try {
                return getContents($url, $headers);
            } catch (HttpException $e) {
                $lastException = $e;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new \Exception('Failed to fetch URL');
    }

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
        $headers = self::HEADERS;
        $response = $this->getContentsWithFallback($url, $headers);

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
                try {
                    $response = $this->getContentsWithFallback($url, $headers);
                } catch (HttpException $e) {
                }

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
        $headers = self::HEADERS;
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
