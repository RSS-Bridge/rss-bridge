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
        ]
    ];

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function collectData()
    {
        $url = 'https://rssscribblehub.com/rssfeed.php?type=';
        if ($this->queriedContext === 'Author') {
            $url = $url . 'author&uid=' . $this->getInput('uid');
        } else { //All and Series use the same source feed
            $url = $url . 'main';
        }
        $this->collectExpandableDatas($url);
    }

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

        try {
            $dom = getSimpleHTMLDOMCached($item['uri']);
        } catch (HttpException $e) {
            // 403 Forbidden, This means we got anti-bot response
            if ($e->getCode() === 403) {
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
        $item['uid'] = $item_sid . "/$item_pid";

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
                        return $item;
                    }
                    throw $e;
                }
                $title = html_entity_decode($page->find('.fic_title', 0)->plaintext);
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
        }
        return $uri;
    }
}
