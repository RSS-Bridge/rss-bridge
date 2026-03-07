<?php

class ScribbleHubBridge extends FeedExpander
{
    const MAINTAINER = 'phantop';
    const NAME = 'Scribble Hub';
    const URI = 'https://scribblehub.com/';
    const DESCRIPTION = 'Returns chapters from Scribble Hub.';
    const PARAMETERS = [
        'Author' => [
            'uid' => [
                'name' => 'uid',
                'required' => true,
                // Example: miriamrobern's stories
                'exampleValue' => '149271',
            ],
        ],
        'List' => [
            'url' => [
                'name' => 'url',
                'required' => true,
                // Example: latest stories with the 'Transgender' tag
                'exampleValue' => 'https://www.scribblehub.com/series-finder/?sf=1&gi=6&tgi=1088&sort=dateadded',
            ],
        ],
        'Series' => [
            'sid' => [
                'name' => 'sid',
                'required' => true,
                // Example: latest chapters from Uskweirs
                'exampleValue' => '965299',
            ],
        ],
    ];

    const FEEDURI = 'https://rssscribblehub.com/rssfeed.php?type=author&uid=';
    public function collectData()
    {
        if ($this->queriedContext === 'Author') {
            $this->collectExpandableDatas(self::FEEDURI . $this->getInput('uid'));
        }
        if ($this->queriedContext === 'List') {
            $this->collectList($this->getURI());
        }
        if ($this->queriedContext === 'Series') {
            $this->collectSeries($this->getURI());
        }
    }

    protected $author = '';
    protected function parseItem(array $item)
    {
        $this->author = $item['author'];
        $item['comments'] = $item['uri'] . '#comments';
        $item['uid'] = $item['uri'];

        try {
            $dom = getSimpleHTMLDOMCached($item['uri']);
            $dom = defaultLinkTo($dom, self::URI);
        } catch (HttpException $e) {
            // 403 Forbidden, This means we got anti-bot response
            if ($e->getCode() === 403 || $e->getCode() === 429) {
                return $item;
            }
            throw $e;
        }


        //Retrieve full description from page contents
        $item['content'] = $dom->find('#chp_raw', 0);

        //Retrieve image for thumbnail
        $item_image = $dom->find('.s_novel_img > img', 0)->src;
        $item['enclosures'] = [$item_image];

        //Restore lost categories
        $item_story = html_entity_decode($dom->find('.chp_byauthor > a', 0)->innertext);
        $item_sid   = $dom->find('#mysid', 0)->value;
        $item['categories'] = [$item_story, $item_sid];

        //Generate UID
        $item_pid = $dom->find('#mypostid', 0)->value;

        return $item;
    }

    private function collectList($url)
    {
        $html = getSimpleHTMLDOMCached($url);
        foreach ($html->find('.search_main_box') as $element) {
            $title = $element->find('.search_title a', 0);
            $strdate = $element->find('[title="Last Updated"]', 0)->plaintext;
            $item = [
                'author' => $element->find('[title="Author"]', 0)->plaintext,
                'content' => str_get_html($element->find('.search_body', 0)),
                'enclosures' => [$element->find('.search_img img', 0)->src],
                'timestamp' => strtotime($strdate),
                'title' => $title->plaintext,
                'uri' => $title->href,
                'uid' => $title->href,
            ];


            foreach ($element->find('.fic_genre') as $tag) {
                $item['categories'][] = $tag->plaintext;
            }
            // Need to clean listing content to get real description
            foreach ($item['content']->firstChild()->children() as $child) {
                $child->remove();
            }

            try {
                $details = getSimpleHTMLDOMCached($item['uri']);
                $details = defaultLinkTo($details, self::URI);
            } catch (HttpException $e) {
                // 403 Forbidden, This means we got anti-bot response
                if ($e->getCode() === 403 || $e->getCode() === 429) {
                    $this->items[] = $item;
                    continue;
                }
                throw $e;
            }
            // Can get better description from full details page
            $item['content'] = $details->find('.wi_fic_desc', 0);
            $item['enclosures'] = [$details->find('.fic_image img', 0)->src];

            foreach ($details->find('.stag') as $tag) {
                $item['categories'][] = $tag->plaintext;
            }

            $read_url = $details->find('.read_buttons a', 0)->href;
            $item['comments'] = $read_url . '#comments';
            // Attempt to add first chapter
            try {
                $read_html = getSimpleHTMLDOMCached($read_url);
                $read_html = defaultLinkTo($read_html, self::URI);
            } catch (HttpException $e) {
                // 403 Forbidden, This means we got anti-bot response
                if ($e->getCode() === 403 || $e->getCode() === 429) {
                    $this->items[] = $item;
                    continue;
                }
                throw $e;
            }
            $item['content'] .= "<hr><h3><a href=\"$read_url\">";
            $item['content'] .= $read_html->find('.chapter-title', 0);
            $item['content'] .= '</a></h3>';
            $item['content'] .= $read_html->find('#chp_raw', 0);

            $this->items[] = $item;
        }
    }

    protected $title = '';
    private function collectSeries($url)
    {
        $html = getSimpleHTMLDOMCached($url, opts: [CURLOPT_COOKIE => 'toc_show=999']);
        $author = $html->find('.auth_name_fic', 0)->plaintext;
        $this->title = $html->find('.fic_title', 0)->plaintext;
        $categories = [
            $author,
            $this->title,
            $this->getInput('sid'),
        ];
        foreach ($html->find('.fic_genre') as $tag) {
            $categories[] = $tag->plaintext;
        }
        foreach ($html->find('.toc_w') as $chapter) {
            $item = [
                'author' => $author,
                'categories' => $categories,
                'comments' => $chapter->find('.toc_a', 0)->href . '#comments',
                'timestamp' => strtotime($chapter->find('.fic_date_pub', 0)->title),
                'title' => $chapter->find('.toc_a', 0)->plaintext,
                'uri' => $chapter->find('.toc_a', 0)->href,
            ];
            try {
                $chapter_html = getSimpleHTMLDOMCached($item['uri']);
                $chapter_html = defaultLinkTo($chapter_html, self::URI);
            } catch (HttpException $e) {
                // 403 Forbidden, This means we got anti-bot response
                if ($e->getCode() === 403 || $e->getCode() === 429) {
                    $this->items[] = $item;
                    continue;
                }
                throw $e;
            }
            $item['content'] = $chapter_html->find('#chp_raw', 0);

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function getName()
    {
        $name = parent::getName() . " $this->queriedContext";
        switch ($this->queriedContext) {
            case 'Author':
                $title = $this->author;
                break;
            case 'List':
                $page = getSimpleHTMLDOMCached($this->getURI());
                $title = $page->find('head > title', 0)->plaintext;
                $title = explode(' |', $title)[0];
                break;
            case 'Series':
                $title = $this->title;
                break;
        }
        if (isset($title)) {
            $name .= " - $title";
        }
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI();
        switch ($this->queriedContext) {
            case 'Author':
                $uri = self::URI . 'profile/' . $this->getInput('uid');
                break;
            case 'List':
                $uri = $this->getInput('url');
                break;
            case 'Series':
                $uri = self::URI . 'series/' . $this->getInput('sid') . '/a';
                break;
        }
        return $uri;
    }
}
