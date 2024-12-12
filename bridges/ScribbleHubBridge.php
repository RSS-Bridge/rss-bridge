<?php

class ScribbleHubBridge extends FeedExpander
{
    const MAINTAINER = 'phantop';
    const NAME = 'Scribble Hub';
    const URI = 'https://scribblehub.com/';
    const DESCRIPTION = 'Returns chapters from Scribble Hub.';
    const PARAMETERS = [
        'All' => [],
        'Author' => [
            'uid' => [
                'name' => 'uid',
                'required' => true,
                // Example: miriamrobern's stories
                'exampleValue' => '149271',
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
        'List' => [
            'url' => [
                'name' => 'url',
                'required' => true,
                // Example: latest stories with the 'Transgender' tag
                'exampleValue' => 'https://www.scribblehub.com/series-finder/?sf=1&gi=6&tgi=1088&sort=dateadded',
            ],
        ]
    ];

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function collectData()
    {
        $url = 'https://rssscribblehub.com/rssfeed.php?type=';
        if ($this->queriedContext === 'List') {
            $this->collectList($this->getURI());
            return;
        }
        if ($this->queriedContext === 'Author') {
            $url = $url . 'author&uid=' . $this->getInput('uid');
        } else { //All and Series use the same source feed
            $url = $url . 'main';
        }
        $this->collectExpandableDatas($url);
    }

    private function collectList($url)
    {
        $html = getSimpleHTMLDOMCached($url);
        foreach ($html->find('.search_main_box') as $element) {
            $item = [];

            $item['author'] = $element->find('[title="Author"]', 0)->plaintext;
            $item['enclosures'] = [$element->find('.search_img img', 0)->src];
            $title = $element->find('.search_title a', 0);
            $item['title'] = $title->plaintext;
            $item['uri'] = $title->href;
            $item['uid'] = $item['uri'];

            $strdate = $element->find('[title="Last Updated"]', 0)->plaintext;
            $item['timestamp'] = strtotime($strdate);

            foreach ($element->find('.fic_genre') as $tag) {
                $item['categories'][] = $tag->plaintext;
            }

            // Get minimal description in case further requests fail
            $item['content'] = str_get_html($element->find('.search_body', 0));
            foreach ($item['content']->firstChild()->children() as $child) {
                $child->remove();
            }

            try {
                $details = getSimpleHTMLDOMCached($item['uri']);
            } catch (HttpException $e) {
                // 403 Forbidden, This means we got anti-bot response
                if ($e->getCode() === 403 || $e->getCode() === 429) {
                    $this->items[] = $item;
                    continue;
                }
                throw $e;
            }
            $item['enclosures'] = [$details->find('.fic_image img', 0)->src];
            $item['content'] = $details->find('.wi_fic_desc', 0);

            foreach ($details->find('.stag') as $tag) {
                $item['categories'][] = $tag->plaintext;
            }

            $read_url = $details->find('.read_buttons a', 0)->href;
            $item['comments'] = $read_url . '#comments';
            try {
                $read_html = getSimpleHTMLDOMCached($read_url);
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

    protected $author = '';

    protected function parseItem(array $item)
    {
        //For series, filter out other series from 'All' feed
        if (
            $this->queriedContext === 'Series'
            && preg_match('/read\/' . $this->getInput('sid') . '-/', $item['uri']) !== 1
        ) {
            return [];
        }

        if ($this->queriedContext === 'Author') {
            $this->author = $item['author'];
        }

        $item['comments'] = $item['uri'] . '#comments';
        $item['uid'] = $item['uri'];

        try {
            $dom = getSimpleHTMLDOMCached($item['uri']);
        } catch (HttpException $e) {
            // 403 Forbidden, This means we got anti-bot response
            if ($e->getCode() === 403 || $e->getCode() === 429) {
                return $item;
            }
            throw $e;
        }

        $dom = defaultLinkTo($dom, self::URI);

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

    public function getName()
    {
        $name = parent::getName() . " $this->queriedContext";
        switch ($this->queriedContext) {
            case 'Author':
                $title = $this->author;
                break;
            case 'Series':
                try {
                    $page = getSimpleHTMLDOMCached(self::URI . 'series/' . $this->getInput('sid') . '/a');
                } catch (HttpException $e) {
                    // 403 Forbidden, This means we got anti-bot response
                    if ($e->getCode() === 403) {
                        return $name;
                    }
                    throw $e;
                }
                $title = html_entity_decode($page->find('.fic_title', 0)->plaintext);
                break;
            case 'List':
                $page = getSimpleHTMLDOMCached($this->getURI());
                $title = $page->find('head > title', 0)->plaintext;
                $title = explode(' |', $title)[0];
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
            case 'Series':
                $uri = self::URI . 'series/' . $this->getInput('sid') . '/a';
                break;
            case 'List':
                $uri = $this->getInput('url');
                break;
        }
        return $uri;
    }
}
