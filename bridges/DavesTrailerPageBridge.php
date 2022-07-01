<?php

class DavesTrailerPageBridge extends BridgeAbstract
{
    const MAINTAINER = 'johnnygroovy';
    const NAME = 'Daves Trailer Page Bridge';
    const URI = 'https://www.davestrailerpage.co.uk/';
    const DESCRIPTION = 'Last trailers in HD thanks to Dave.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(static::URI)
        or returnClientError('No results for this query.');

        $curr_date = null;
        foreach ($html->find('tr') as $tr) {
            // If it's a date row, update the current date
            if ($tr->align == 'center') {
                $curr_date = $tr->plaintext;
                continue;
            }

            $item = [];

            // title
            $item['title'] = $tr->find('td', 0)->find('b', 0)->plaintext;

            // content
            $item['content'] = $tr->find('ul', 1);

            // uri
            $item['uri'] = $tr->find('a', 3)->getAttribute('href');

            // date: parsed by FeedItem using strtotime
            $item['timestamp'] = $curr_date;

            $this->items[] = $item;
        }
    }
}
