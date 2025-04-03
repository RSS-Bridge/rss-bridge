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
        for ($i = 0; $i < 5; $i++) {
            if (isset($dateObj)) {
                $dateObj->modify('-1 day');
                $publishedUri = $dateObj->format('/Y/m/d');
            } else {
                $publishedUri = '';
            }

            $html = getSimpleHTMLDOM($this->getURI() . $publishedUri);
            $page = $html->find('div[class^="ComicViewer_comicViewer__comic__"] script[type="application/ld+json"]', 0);
            $json = Json::decode($page->innertext);

            $dateObj = DateTime::createFromFormat('F j, Y', $json['datePublished']);

            $item = [];
            $item['id'] = $json['contentUrl'];
            $item['uri'] = $this->getURI() . $dateObj->format('/Y/m/d');
            $item['author'] = $json['author']['name'];
            $item['title'] = 'GoComics ' . $json['name'];
            $item['timestamp'] = $dateObj->getTimestamp();
            $item['enclosures'][] = $json['contentUrl'];
            $item['content'] = '<img src="' . $json['contentUrl'] . '" alt="">';

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

    public function detectParameters($url)
    {
        if (preg_match('#^' . self::URI . '([-a-z0-9]+)$#i', $url, $matches)) {
            return ['comicname' => $matches[1]];
        }
    }
}
