<?php

class InvidiousHTMLBridge extends BridgeAbstract
{
    const NAME = 'Invidious HTML Scraper';
    const DESCRIPTION = 'Scrapes Invidious without relying on the Invidious RSS endpoint';
    const MAINTAINER = 'Avvyxx';
    const CACHE_TIMEOUT = 60 * 60 * 24; // 24 hours
    const PARAMETERS = [[
        'invidious' => [
            'name' => 'Invidious host',
            'required' => true,
            'exampleValue' => 'invidious.avyx.home',
        ],
        'channel' => [
            'name' => 'Channel ID',
            'required' => true,
            'exampleValue' => 'UC7YOGHUfC1Tb6E4pudI9STA',
        ],
        'hideshorts' => [
            'name' => 'Include shorts',
            'type' => 'checkbox',
            'title' => 'Include shorts'
        ]
    ]];

    public function collectData()
    {
      $url = 'https://' . $this->getInput('invidious') . '/channel/' . $this->getInput('channel') . '/videos';

      $html = getSimpleHTMLDOMCached($url);

      foreach ($html->find('.pure-u-md-1-4') as $yt_item) {
        $this->collectVideoData($yt_item);
      }
    }

    private function collectVideoData($yt_item) {
      $video_id = $this->findVideoID($yt_item);
      $video_title = $this->findVideoTitle($yt_item);

      $item['title'] = $video_title;
      $item['content'] = $video_id;

      $this->items[] = $item;
    }

    private function findVideoID($yt_item) {
      $href = $yt_item->getElementByTagName('a')->href;

      parse_str(parse_url($href, PHP_URL_QUERY), $params);

      return $params['v'];
    }

    private function findVideoTitle($yt_item) {
      $a = $yt_item->getElementsByTagName('a')[1];
      $p = $a->firstChild();
      return $p->innertext;
    }
}
