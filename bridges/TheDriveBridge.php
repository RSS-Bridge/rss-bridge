<?php

class TheDriveBridge extends FeedExpander
{
    const NAME = 'The Drive';
    const URI = 'https://www.thedrive.com/';
    const DESCRIPTION = 'Car news from thedrive.com';
    const MAINTAINER = 't0stiman';
    const DONATION_URI = 'https://ko-fi.com/tostiman';

    public function collectData()
    {
        $this->collectExpandableDatas('https://www.thedrive.com/feed', 20);
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        //remove warzone articles
        if (str_contains($item['uri'], 'the-war-zone')) {
            return null;
        }

        //the first image in the article is an attachment for some reason
        foreach ($item['enclosures'] as $attachment) {
            $item['content'] = '<img src="' . $attachment . '">' . $item['content'];
        }
        $item['enclosures'] = [];

        //make youtube videos clickable
        $html = str_get_html($item['content']);

        foreach ($html->find('div.lazied-youtube-frame') as $youtubeVideoDiv) {
            $videoID = $youtubeVideoDiv->getAttribute('data-video-id');

            //place <a> around the <div>
            $youtubeVideoDiv->outertext = '<a href="https://www.youtube.com/watch?v=' . $videoID . '">' . $youtubeVideoDiv->outertext . '</a>';
        }

        $item['content'] = $html;

        return $item;
    }
}
