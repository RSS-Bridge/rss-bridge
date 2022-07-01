<?php

class PCGWNewsBridge extends FeedExpander
{
    const MAINTAINER = 'somini';
    const NAME = 'PCGamingWiki News';
    const BASE_URI = 'https://www.pcgamingwiki.com';
    const URI = self::BASE_URI . '/wiki/PCGamingWiki:News';
    const DESCRIPTION = 'PCGW News Feed';

    public function getIcon()
    {
        return 'https://static.pcgamingwiki.com/favicons/pcgamingwiki.png';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $now = strtotime('now');

        foreach ($html->find('.mw-parser-output .news_li') as $element) {
            $item = [];

            $date_string = $element->find('b', 0)->innertext;
            $date = strtotime($date_string);
            if ($date > $now) {
                $date = strtotime($date_string . ' - 1 year');
            }
            $item['title'] = self::NAME . ' for ' . date('Y-m-d', $date);
            $item['content'] = $element;
            $item['uri'] = $this->getURI();
            $item['timestamp'] = $date;

            $this->items[] = $item;
        }
    }
}
