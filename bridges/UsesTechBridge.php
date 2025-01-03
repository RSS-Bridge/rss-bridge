<?php

class UsesTechbridge extends BridgeAbstract
{
    const NAME        = '/uses';
    const URI         = 'https://uses.tech/';
    const DESCRIPTION = 'RSS feed for /uses';
    const MAINTAINER  = 'jummo4@yahoo.de';
    const MAX_ITEM      = 100; # Maximum items to loop through which works fast enough on my computer

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('div[class=PersonInner]') as $index => $a) {
            $item = []; // Create an empty item
            $articlePath = $a->find('a[class=displayLink]', 0)->href;
            $item['title'] = $a->find('img', 0)->getAttribute('alt');
            $item['author'] = $a->find('img', 0)->getAttribute('alt');
            $item['uri'] = $articlePath;
            $item['content'] = $a->find('p', 0)->innertext;

            $this->items[] = $item; // Add item to the list
            if (count($this->items) >= self::MAX_ITEM) {
                break;
            }
        }
    }
}
