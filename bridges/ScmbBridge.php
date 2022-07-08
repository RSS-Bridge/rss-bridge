<?php

class ScmbBridge extends BridgeAbstract
{
    const MAINTAINER = 'Astalaseven';
    const NAME = 'Se Coucher Moins Bête Bridge';
    const URI = 'https://secouchermoinsbete.fr';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Returns the newest anecdotes.';

    public function collectData()
    {
        $html = '';
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('article') as $article) {
            $item = [];
            $item['uri'] = self::URI . $article->find('p.summary a', 0)->href;
            $item['title'] = $article->find('header h1 a', 0)->innertext;

            // remove text "En savoir plus" from anecdote content
            $readMoreButton = $article->find('span.read-more', 0);
            if ($readMoreButton) {
                $readMoreButton->outertext = '';
            }
            $content = $article->find('p.summary a', 0)->innertext;

            // remove superfluous spaces at the end
            $content = substr($content, 0, strlen($content) - 17);

            // get publication date
            $str_date = $article->find('time', 0)->datetime;
            [$date, $time] = explode(' ', $str_date);
            [$y, $m, $d] = explode('-', $date);
            [$h, $i] = explode(':', $time);
            $timestamp = mktime($h, $i, 0, $m, $d, $y);
            $item['timestamp'] = $timestamp;

            $item['content'] = $content;
            $this->items[] = $item;
        }
    }
}
