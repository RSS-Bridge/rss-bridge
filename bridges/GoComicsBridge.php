<?php

class GoComicsBridge extends BridgeAbstract
{
    const MAINTAINER = 'sky';
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
        ]
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        //Get info from first page
        $author = preg_replace('/By /', '', $html->find('.media-subheading', 0)->plaintext);

        $link = self::URI . $html->find('.gc-deck--cta-0', 0)->find('a', 0)->href;
        for ($i = 0; $i < 5; $i++) {
            $item = [];

            $page = getSimpleHTMLDOM($link);
            $imagelink = $page->find('.comic.container', 0)->getAttribute('data-image');
            $date = explode('/', $link);

            $item['id'] = $imagelink;
            $item['uri'] = $link;
            $item['author'] = $author;
            $item['title'] = 'GoComics ' . $this->getInput('comicname');
            $item['timestamp'] = DateTime::createFromFormat('Ymd', $date[5] . $date[6] . $date[7])->getTimestamp();
            $item['content'] = '<img src="' . $imagelink . '" />';

            $link = self::URI . $page->find('.js-previous-comic', 0)->href;
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
