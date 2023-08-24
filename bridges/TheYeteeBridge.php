<?php

class TheYeteeBridge extends BridgeAbstract
{
    const MAINTAINER = 'Monsieur Poutounours';
    const NAME = 'TheYetee';
    const URI = 'https://theyetee.com';
    const CACHE_TIMEOUT = 14400; // 4 h
    const DESCRIPTION = 'Fetch daily shirts from The Yetee';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $div = $html->find('.module_timed-item.is--full');
        foreach ($div as $element) {
            $item = [];
            $item['enclosures'] = [];

            $title = $element->find('h2', 0)->plaintext;
            $item['title'] = $title;

            $author = trim($element->find('.module_timed-item--artist a', 0)->plaintext);
            $item['author'] = $author;

            $item['uri'] = static::URI;

            $content = '<p>' . $title . ' by ' . $author . '</p>';
            $photos = $element->find('a.img');
            foreach ($photos as $photo) {
                $content = $content . "<br /><img src='$photo->href' />";
                $item['enclosures'][] = $photo->src;
            }
            $item['content'] = $content;

            $this->items[] = $item;
        }
    }
}
