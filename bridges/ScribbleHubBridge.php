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
                // Example: Alyson Greaves's stories
                'exampleValue' => '76208',
            ],
        ],
        'Series' => [
            'sid' => [
                'name' => 'sid',
                'required' => true,
                // Example: latest chapters from The Sisters of Dorley by Alyson Greaves
                'exampleValue' => '421879',
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

    protected function parseItem($newItem)
    {
        $item = parent::parseItem($newItem);

        //For series, filter out other series from 'All' feed
        if (
            $this->queriedContext === 'Series'
            && preg_match('/read\/' . $this->getInput('sid') . '-/', $item['uri']) !== 1
        ) {
            return [];
        }

        $item['comments'] = $item['uri'] . '#comments';

        try {
            $item_html = getSimpleHTMLDOMCached($item['uri']);
        } catch (HttpException $e) {
            // 403 Forbidden, This means we got anti-bot response
            if ($e->getCode() === 403) {
                return $item;
            }
            throw $e;
        }

        $item_html = defaultLinkTo($item_html, self::URI);

        //Retrieve full description from page contents
        $item['content'] = $item_html->find('#chp_raw', 0);

        //Retrieve image for thumbnail
        $item_image = $item_html->find('.s_novel_img > img', 0)->src;
        $item['enclosures'] = [$item_image];

        //Restore lost categories
        $item_story = html_entity_decode($item_html->find('.chp_byauthor > a', 0)->innertext);
        $item_sid   = $item_html->find('#mysid', 0)->value;
        $item['categories'] = [$item_story, $item_sid];

        //Generate UID
        $item_pid = $item_html->find('#mypostid', 0)->value;
        $item['uid'] = $item_sid . "/$item_pid";

        return $item;
    }

    public function getName()
    {
        $name = parent::getName() . " $this->queriedContext";
        switch ($this->queriedContext) {
            case 'Author':
                try {
                    $page = getSimpleHTMLDOMCached(self::URI . 'profile/' . $this->getInput('uid'));
                } catch (HttpException $e) {
                    // 403 Forbidden, This means we got anti-bot response
                    if ($e->getCode() === 403) {
                        return $name;
                    }
                    throw $e;
                }
                $title = html_entity_decode($page->find('.p_m_username.fp_authorname', 0)->plaintext);
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
