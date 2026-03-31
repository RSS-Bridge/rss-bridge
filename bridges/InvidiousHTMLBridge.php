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
            'exampleValue' => 'iv.domain.tld',
        ],
        'channel' => [
            'name' => 'Channel ID',
            'required' => true,
            'exampleValue' => 'UC7YOGHUfC1Tb6E4pudI9STA',
        ],
        'includedescription' => [
            'name' => 'Include description',
            'type' => 'checkbox',
            'title' => 'Include description'
        ],
        'includeshorts' => [
            'name' => 'Include shorts',
            'type' => 'checkbox',
            'title' => 'Include shorts'
        ],
        'max_releases' => [
            'name' => 'max. number of releases',
            'type' => 'number',
            'title' => 'Maximum number of releases to include. Same for both videos and shorts.',
            'exampleValue' => 5,
        ]
    ]];

    private $video_info = [];

    public function collectData() {
      $max_releases = $this->getInput('max_releases');

      $videos_html = $this->getFeedType('videos');

      $i = 0;
      foreach ($videos_html->find('.pure-u-md-1-4') as $yt_item) {
        if ($max_releases == null || $i < $max_releases) {
          $this->collectVideoData($yt_item);
          $i += 1;
        }
      }

      if ($this->getInput('includeshorts')) {
        $shorts_html = $this->getFeedType('shorts');

        $i = 0;
        foreach ($shorts_html->find('.pure-u-md-1-4') as $yt_item) {
          if ($max_releases == null || $i < $max_releases) {
            $this->collectVideoData($yt_item);
            $i += 1;
          }
        }
      }
    }

    private function getFeedType($feed_type) {
      $url = 'https://' . $this->getInput('invidious') . '/channel/' . $this->getInput('channel') . '/' . $feed_type;
      return getSimpleHTMLDOMCached($url);
    }

    private function collectVideoData($yt_item) {
      $this->video_info['id']            = $this->findVideoID($yt_item);
      $this->video_info['title']         = $this->findVideoTitle($yt_item);
      $this->video_info['description']   = $this->findVideoDescription($yt_item);
      $this->video_info['thumbnail_uri'] = $this->findVideoThumbnailURI($yt_item);

      $this->addItem();
    }

    private function addItem() {
      $item['uri']        = 'https://' . $this->getInput('invidious') . '/watch?v=' . $this->video_info['id'];
      $item['title']      = $this->video_info['title'];
      $item['timestamp']  = '';
      $item['author']     = $this->getInput('channel');
      $item['content']    = sprintf('<a href="%s"><img src="%s" /></a><br /><div>%s</div>', $item['uri'], $this->video_info['thumbnail_uri'], $this->video_info['description']);
      $item['uid']        = $this->video_info['id'];

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

    private function findVideoDescription($yt_item) {
      if (!$this->getInput('includedescription')) {
        return '';
      }

      $url = 'https://' . $this->getInput('invidious') . '/watch?v=' . $this->video_info['id'];

      $html = getSimpleHTMLDOMCached($url);
      $document_wrapper = $html->getElementById('descriptionWrapper');
      return $document_wrapper->innertext;
    }

    private function findVideoThumbnailURI($yt_item) {
      $img = $yt_item->getElementByTagName('img');
      return 'https://' . $this->getInput('invidious') . $img->src;
    }
}
