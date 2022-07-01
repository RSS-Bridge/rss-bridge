<?php

class KhinsiderBridge extends BridgeAbstract
{
    const MAINTAINER = 'Chouchenos';
    const NAME = 'Khinsider';
    const URI = 'https://downloads.khinsider.com/';
    const CACHE_TIMEOUT = 14400; // 4 h
    const DESCRIPTION = 'Fetch daily game OST from Khinsider';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $dates = $html->find('.latestSoundtrackHeading');
        $tables = $html->find('.albumList');
        // $dates is empty
        foreach ($dates as $i => $date) {
            $item = [];
            $item['uri'] = self::URI;
            $item['timestamp'] = DateTime::createFromFormat('F jS, Y', $date->plaintext)->setTime(1, 1)->format('U');
            $item['title'] = sprintf('OST for %s', $date->plaintext);
            $item['author'] = 'Khinsider';
            $trs = $tables[$i]->find('tr');
            $content = '<ul>';
            foreach ($trs as $tr) {
                $td = $tr->find('td', 1);
                if (null !== $td) {
                    $link = $td->find('a', 0);
                    $content .= sprintf('<li><a href="%s">%s</a></li>', $link->href, $link->plaintext);
                }
            }
            $content .= '</ul>';
            $item['content'] = $content;
            $item['uid'] = $item['timestamp'];
            $item['categories'] = ['Video games', 'Music', 'OST', 'download'];

            $this->items[] = $item;
        }
    }
}
