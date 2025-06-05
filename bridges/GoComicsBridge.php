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
        $landingpage = getSimpleHTMLDOM($link);

        $link = $landingpage->find('div[data-post-url]', 0)->getAttribute('data-post-url');

        for ($i = 0; $i < $this->getInput('limit'); $i++) {
            $html = getSimpleHTMLDOM($link);

            $imagelink = $html->find('meta[property="og:image"]', 0)->content;
            $parts = explode('/', $link);
            $date = DateTime::createFromFormat('Y/m/d', implode('/', array_slice($parts, -3)));
            $title = $html->find('meta[property="og:title"]', 0)->content;
            preg_match('/by (.*?) for/', $title, $authormatches);
            $author = $authormatches[1] ?? 'GoComics';

            $item = [];
            $item['id'] = $imagelink;
            $item['uri'] = $link;
            $item['author'] = $author;
            $item['title'] = 'GoComics ' . $this->getInput('comicname');
            if ($this->getInput('date-in-title') === true) {
                $item['title'] = $title;
            }
            $item['timestamp'] = $date->setTime(0, 0, 0)->getTimestamp();
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
