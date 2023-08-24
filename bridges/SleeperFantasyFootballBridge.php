<?php

class SleeperFantasyFootballBridge extends BridgeAbstract
{
    const NAME = 'Sleeper.com Alerts';
    const URI = 'https://sleeper.com/topics/170000000000000000';
    const DESCRIPTION = 'Fantasy Football Alerts from Sleeper.com';
    const MAINTAINER = 'piyushpaliwal';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached(self::URI, self::CACHE_TIMEOUT);
        foreach ($html->find('div.content > div.latest-topics > a') as $index => $a) {
            $content = $a->find('div.title > p', 0)->innertext;
            $meta = $this->processString($a->find('div.desc > div.username', 0)->innertext);
            $item['title'] = $content;
            $item['content'] = $content;
            $item['categories'] = $a->find('div.title div.tag', 0)->innertext;
            $item['timestamp'] = $meta['timestamp'];
            $item['author'] = $meta['author'];
            $item['enclosures'] = $a->find('div.player-photo amp-img', 0)->src;
            $this->items[] = $item;
            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    protected function processString($inputString)
    {
        $decodedString = str_replace(['&nbsp;', '&#8226;'], [' ', '|'], $inputString);
        $splitArray = explode(' | ', $decodedString);
        $author = trim($splitArray[0]);
        $timeString = trim($splitArray[1]);
        $timestamp = strtotime($timeString);
        return [
            'author' => $author,
            'timestamp' => $timestamp
        ];
    }
}
