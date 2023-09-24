<?php

class LaRocheSurYonEventsBridge extends BridgeAbstract
{
    const NAME = 'Events from La Roche Sur Yon Agglomeration';
    const URI = 'https://www.larochesuryon.fr/agenda/';
    const DESCRIPTION = 'Events from the La Roche Sur Yon Agglomeration website';
    const MAINTAINER = 'marius851000';

    public function collectData()
    {
        $dom = getSimpleHTMLDOM('https://www.larochesuryon.fr/agenda/');
        foreach ($dom->find('.page-agenda ul .agenda') as $li) {
            $item = [];

            $item['title'] = $li->find('.header', 0)->plaintext;
            $item['uri'] = 'https://www.larochesuryon.fr' . $li->find('a', 0)->href;
            $item['categories'] = [];
            foreach (explode('/', $li->find('.news-list-category', 0)->plaintext) as $category) {
                $item['categories'][] = trim($category);
            }
            $item['enclosures'][] = 'https://www.larochesuryon.fr' . $li->find('.news-img-wrap img', 0)->src;
            // For range, first date is first day it happen and the second is last day it happen.
            // For single-day event, it is the actual day.
            $item['timestamp'] = end($li->find('time'))->datetime;

            $this->items[] = $item;
        }
    }
}
