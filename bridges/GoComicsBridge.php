<?php

class GoComicsBridge extends BridgeAbstract
{
    const MAINTAINER = 'TReKiE';
    //const MAINTAINER = 'sky';
    const NAME = 'GoComics Unofficial RSS';
    const URI = 'https://www.gocomics.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'The Unofficial GoComics RSS';
    const PARAMETERS = [ [
        'comicname' => [
            'name' => 'comicname',
            'type' => 'text',
            'exampleValue' => 'heartofthecity',
            'required' => true
        ],
        'date-in-title' => [
            'name' => 'Add date and full name to each day\'s title',
            'type' => 'checkbox',
            'title' => 'Adds the date and the full name into the title of each day\'s comic',
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'title' => 'The number of recent comics to get',
            'defaultValue' => 5
        ]
    ]];

    public function collectData()
    {
        $link = $this->getURI();

        for ($i = 0; $i < $this->getInput('limit'); $i++) {
            $html = getSimpleHTMLDOM($link);
            // get json data from the first page
            $json = $html->find('div[class^="ShowComicViewer_showComicViewer__comic__"] script[type="application/ld+json"]', 0)->innertext;
            $data = json_decode($json, false);

            $item = [];

            $author = $data->author->name;
            $imagelink = $data->contentUrl;
            $date = $data->datePublished;
            $title = $data->name . ' - GoComics';

            // get a permlink for this day's comic if there isn't one specified
            if ($link === $this->getURI()) {
                $link = $this->getURI() . '/' . DateTime::createFromFormat('F j, Y', $date)->format('Y/m/d');
            }

            $item['id'] = $imagelink;
            $item['uri'] = $link;
            $item['author'] = $author;
            $item['title'] = 'GoComics ' . $this->getInput('comicname');
            if ($this->getInput('date-in-title') === true) {
                $item['title'] = $title;
            }
            $item['timestamp'] = DateTime::createFromFormat('F j, Y', $date)->setTime(0, 0, 0)->getTimestamp();
            $item['content'] = '<img src="' . $imagelink . '" />';

            $link = rtrim(self::URI, '/') . $html->find('a[class*="ComicNavigation_controls__button_previous__"]', 0)->href;
            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('comicname'))) {
            return self::URI . urlencode($this->getInput('comicname'));
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('comicname'))) {
            return $this->getInput('comicname') . ' - GoComics';
        }

        return parent::getName();
    }
}
