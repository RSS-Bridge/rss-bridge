<?php

class TomsToucheBridge extends BridgeAbstract
{
    const NAME = 'Toms Touché';
    const URI = 'https://taz.de/#!tom=tomdestages';
    const DESCRIPTION = 'Your daily dose of Toms Touche.';
    const MAINTAINER = 'latz';
    const CACHE_TIMEOUT = 3600; // 1h

    public function collectData()
    {
        $url = 'https://taz.de/';
        $html = getSimpleHTMLDOM($url); // Docs: https://simplehtmldom.sourceforge.io/docs/1.9/index.html
        $date = $html->find('p[x-ref]');
        $date = trim($date[0]->innertext);
        [$day, $month, $year] = explode('.', $date);
        $image = $html->find('img[alt="tom des tages"]');

        $item = [];
        $item['title'] = "Toms Touché - $date";
        $item['uri'] = 'https://taz.de/#!tom=tomdestages';
        $item['timestamp'] = mktime(0, 0, 0, $month, $day, $year);
        $item['content'] = $image[0] . '</img>'; // This isn't good HTML style, but at least syntactically correct
        $item['uid'] = $image[0]->getAttribute('src');
        $this->items[] = $item;
    }
}
