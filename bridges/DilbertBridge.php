<?php

class DilbertBridge extends BridgeAbstract
{
    const MAINTAINER = 'kranack';
    const NAME = 'Dilbert Daily Strip';
    const URI = 'https://dilbert.com';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'The Unofficial Dilbert Daily Comic Strip';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('section.comic-item') as $element) {
            $img = $element->find('img', 0);
            $link = $element->find('a', 0);
            $comic = $img->src;
            $title = $img->alt;
            $url = $link->href;
            $date = substr(strrchr($url, '/'), 1);
            if (empty($title)) {
                $title = 'Dilbert Comic Strip on ' . $date;
            }
            $date = strtotime($date);

            $item = [];
            $item['uri'] = $url;
            $item['title'] = $title;
            $item['author'] = 'Scott Adams';
            $item['timestamp'] = $date;
            $item['content'] = '<img src="' . $comic . '" alt="' . $img->alt . '" />';
            $this->items[] = $item;
        }
    }
}
