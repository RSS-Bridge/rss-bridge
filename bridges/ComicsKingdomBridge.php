<?php

class ComicsKingdomBridge extends BridgeAbstract
{
    const MAINTAINER = 'stjohnjohnson';
    const NAME = 'Comics Kingdom Unofficial RSS';
    const URI = 'https://comicskingdom.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Comics Kingdom Unofficial RSS';
    const PARAMETERS = [ [
        'comicname' => [
            'name' => 'comicname',
            'type' => 'text',
            'exampleValue' => 'mutts',
            'title' => 'The name of the comic in the URL after https://comicskingdom.com/',
            'required' => true
        ]
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI(), [], [], true, false);

        // Get author from first page
        $author = $html->find('div.author p', 0);
        ;

        // Get current date/link
        $link = $html->find('meta[property=og:url]', -1)->content;
        for ($i = 0; $i < 3; $i++) {
            $item = [];

            $page = getSimpleHTMLDOM($link);

            $imagelink = $page->find('meta[property=og:image]', 0)->content;

            $date = explode('/', $link);

            $item['id'] = $imagelink;
            $item['uri'] = $link;
            $item['author'] = $author;
            $item['title'] = 'Comics Kingdom ' . $this->getInput('comicname');
            $item['timestamp'] = DateTime::createFromFormat('Y-m-d', $date[count($date) - 1])->getTimestamp();
            $item['content'] = '<img src="' . $imagelink . '" />';

            $this->items[] = $item;
            $link = $page->find('div.comic-viewer-inline a', 0)->href;
            if (empty($link)) {
                break; // allow bridge to continue if there's less than 3 comics
            }
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
            return $this->getInput('comicname') . ' - Comics Kingdom';
        }

        return parent::getName();
    }
}
